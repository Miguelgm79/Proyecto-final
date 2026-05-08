<?php
// ============================================
// admin/dashboard.php
// Listado de reservas del bar asignado al admin con filtros
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereAdmin();

// Si el admin no tiene bar asignado, mostrar aviso
$idBarAdmin = $_SESSION['bar_asignado'] ?? null;

// Información del bar asignado
$nombreBar = '';
if ($idBarAdmin) {
    $stmt = $pdo->prepare("SELECT nombre FROM bares WHERE id = ?");
    $stmt->execute([$idBarAdmin]);
    $nombreBar = $stmt->fetchColumn();
}

// --- Filtros ---
$filtroEstado = $_GET['estado'] ?? 'todas';   // todas | activa | cancelada
$filtroFecha  = $_GET['fecha']  ?? '';        // YYYY-MM-DD
$filtroBuscar = trim($_GET['buscar'] ?? '');  // por nombre/email del usuario

$reservas = [];
if ($idBarAdmin) {
    $sql = "
        SELECT r.*, u.nombre AS nombre_usuario, u.email AS email_usuario
        FROM reservas r
        INNER JOIN usuarios u ON u.id = r.id_usuario
        WHERE r.id_bar = :id_bar
    ";
    $params = [':id_bar' => $idBarAdmin];

    if ($filtroEstado === 'activa' || $filtroEstado === 'cancelada') {
        $sql .= " AND r.estado = :estado";
        $params[':estado'] = $filtroEstado;
    }
    if ($filtroFecha !== '') {
        $sql .= " AND r.fecha_reserva = :fecha";
        $params[':fecha'] = $filtroFecha;
    }
    if ($filtroBuscar !== '') {
        $sql .= " AND (u.nombre LIKE :buscar OR u.email LIKE :buscar)";
        $params[':buscar'] = '%' . $filtroBuscar . '%';
    }

    $sql .= " ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();
}

$tituloPagina = 'Panel de reservas';
require '../includes/header.php';
?>

<h2 class="mb-1">Panel de reservas</h2>
<p class="text-muted">
    <?php if ($nombreBar): ?>
        Restaurante: <strong><?= htmlspecialchars($nombreBar) ?></strong>
    <?php else: ?>
        <span class="text-danger">No tienes ningún restaurante asignado.</span>
    <?php endif; ?>
</p>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php
        $mensajes = [
            'editada'   => 'Reserva actualizada correctamente.',
            'eliminada' => 'Reserva eliminada correctamente.',
        ];
        echo htmlspecialchars($mensajes[$_GET['msg']] ?? 'Operación correcta.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$idBarAdmin): ?>
    <div class="alert alert-warning">
        Tu cuenta de admin no tiene ningún bar asignado. Pídele al administrador
        de la base de datos que actualice el campo <code>bar_asignado</code> en
        tu fila de la tabla <code>usuarios</code>.
    </div>
<?php else: ?>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="todas"     <?= $filtroEstado === 'todas'     ? 'selected' : '' ?>>Todas</option>
                        <option value="activa"    <?= $filtroEstado === 'activa'    ? 'selected' : '' ?>>Activas</option>
                        <option value="cancelada" <?= $filtroEstado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control"
                           value="<?= htmlspecialchars($filtroFecha) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Buscar (nombre o email)</label>
                    <input type="text" name="buscar" class="form-control"
                           value="<?= htmlspecialchars($filtroBuscar) ?>"
                           placeholder="Ej: juan@ejemplo.com">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Resultados:</strong> <?= count($reservas) ?> reserva(s)
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Tlf</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Personas</th>
                        <th>Bebés</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservas)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No hay reservas que coincidan con los filtros.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservas as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
                                <td><small><?= htmlspecialchars($r['email']) ?></small></td>
                                <td><?= htmlspecialchars($r['telefono']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['fecha_reserva']))) ?></td>
                                <td><?= htmlspecialchars(substr($r['hora_reserva'], 0, 5)) ?></td>
                                <td><?= (int)$r['num_personas'] ?></td>
                                <td>
                                    <?php if ($r['bebes']): ?>
                                        Sí (<?= (int)$r['num_bebes'] ?>)
                                    <?php else: ?>
                                        No
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['estado'] === 'activa'): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="editar_reserva.php?id=<?= (int)$r['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="eliminar_reserva.php?id=<?= (int)$r['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Seguro que quieres ELIMINAR esta reserva? Esta acción no se puede deshacer.');">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require '../includes/footer.php'; ?>
