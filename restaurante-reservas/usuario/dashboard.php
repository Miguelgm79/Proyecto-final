<?php
// ============================================
// usuario/dashboard.php
// Listado de reservas del usuario logueado
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereUsuario();

// Obtener reservas del usuario con el nombre del bar
$stmt = $pdo->prepare("
    SELECT r.*, b.nombre AS nombre_bar
    FROM reservas r
    INNER JOIN bares b ON b.id = r.id_bar
    WHERE r.id_usuario = ?
    ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$reservas = $stmt->fetchAll();

$tituloPagina = 'Mis reservas';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Mis reservas</h2>
    <a href="crear_reserva.php" class="btn btn-primary">Nueva reserva</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php
        $mensajes = [
            'creada'    => 'Reserva creada correctamente. Recibirás un email de confirmación.',
            'editada'   => 'Reserva actualizada correctamente.',
            'cancelada' => 'Reserva cancelada. Te hemos enviado un email de confirmación.',
        ];
        echo htmlspecialchars($mensajes[$_GET['msg']] ?? 'Operación correcta.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($reservas)): ?>
    <div class="alert alert-info">
        Todavía no tienes reservas. <a href="crear_reserva.php" class="alert-link">Crea tu primera reserva</a>.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Personas</th>
                        <th>Bebés</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nombre_bar']) ?></td>
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
                                <?php if ($r['estado'] === 'activa'): ?>
                                    <a href="editar_reserva.php?id=<?= (int)$r['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="cancelar_reserva.php?id=<?= (int)$r['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Seguro que quieres cancelar esta reserva?');">
                                        Cancelar
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>
