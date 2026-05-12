<?php
// ============================================
// admin/editar_reserva.php
// El admin puede editar cualquier reserva de su bar
// (incluyendo cambiar el estado)
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email.php';
require_once '../includes/reservas.php';
requiereAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$idBarAdmin = $_SESSION['bar_asignado'] ?? null;
if (!$idBarAdmin) {
    header('Location: dashboard.php');
    exit;
}

// Cargar la reserva (solo si pertenece al bar del admin)
$stmt = $pdo->prepare("
    SELECT r.*, u.nombre AS nombre_usuario
    FROM reservas r
    INNER JOIN usuarios u ON u.id = r.id_usuario
    WHERE r.id = ? AND r.id_bar = ?
");
$stmt->execute([$id, $idBarAdmin]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: dashboard.php');
    exit;
}

$errores = [];
$datos = [
    'num_personas'  => $reserva['num_personas'],
    'fecha_reserva' => $reserva['fecha_reserva'],
    'hora_reserva'  => substr($reserva['hora_reserva'], 0, 5),
    'bebes'         => $reserva['bebes'] ? 'si' : 'no',
    'num_bebes'     => $reserva['num_bebes'],
    'telefono'      => $reserva['telefono'],
    'email'         => $reserva['email'],
    'estado'        => $reserva['estado'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['num_personas']  = (int)($_POST['num_personas'] ?? 0);
    $datos['fecha_reserva'] = trim($_POST['fecha_reserva'] ?? '');
    $datos['hora_reserva']  = trim($_POST['hora_reserva'] ?? '');
    $datos['bebes']         = $_POST['bebes'] ?? 'no';
    $datos['num_bebes']     = (int)($_POST['num_bebes'] ?? 0);
    $datos['telefono']      = trim($_POST['telefono'] ?? '');
    $datos['email']         = trim($_POST['email'] ?? '');
    $datos['estado']        = $_POST['estado'] ?? 'activa';

    if ($datos['num_personas'] < 1)     $errores[] = 'El número de personas debe ser al menos 1.';
    if ($datos['fecha_reserva'] === '') $errores[] = 'La fecha es obligatoria.';
    if ($datos['hora_reserva'] === '')  $errores[] = 'La hora es obligatoria.';
    if ($datos['telefono'] === '')      $errores[] = 'El teléfono es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';
    if (!in_array($datos['estado'], ['activa', 'cancelada'], true)) $errores[] = 'Estado inválido.';

    $tieneBebes = ($datos['bebes'] === 'si');
    if ($tieneBebes && $datos['num_bebes'] < 1) {
        $errores[] = 'Si hay bebés, indica cuántos.';
    }

    // Comprobar disponibilidad solo si la reserva queda en estado 'activa'
    // (si el admin la marca como cancelada, no ocupa plaza)
    if (empty($errores) && $datos['estado'] === 'activa'
        && !diaTieneSitio($pdo, $idBarAdmin, $datos['fecha_reserva'], $id)) {
        $errores[] = 'Ese día ya tienes el máximo de '
                   . LIMITE_RESERVAS_DIARIAS . ' reservas. Elige otro día.';
    }

    if (empty($errores)) {
        // Detectar cambios antes de actualizar
        $cambios = detectarCambios($reserva, $datos, $pdo);

        $stmt = $pdo->prepare("
            UPDATE reservas SET
                num_personas = ?, fecha_reserva = ?, hora_reserva = ?,
                bebes = ?, num_bebes = ?, telefono = ?, email = ?, estado = ?
            WHERE id = ? AND id_bar = ?
        ");
        $stmt->execute([
            $datos['num_personas'],
            $datos['fecha_reserva'],
            $datos['hora_reserva'],
            $tieneBebes ? 1 : 0,
            $tieneBebes ? $datos['num_bebes'] : 0,
            $datos['telefono'],
            $datos['email'],
            $datos['estado'],
            $id,
            $idBarAdmin,
        ]);

        // Avisar al usuario por email solo si hubo cambios reales
        if (!empty($cambios)) {
            $stmt = $pdo->prepare("SELECT nombre FROM bares WHERE id = ?");
            $stmt->execute([$idBarAdmin]);
            $nombreBar = $stmt->fetchColumn();

            emailReservaEditada(
                $datos['email'],
                $reserva['nombre_usuario'],
                $nombreBar,
                date('d/m/Y', strtotime($datos['fecha_reserva'])),
                $datos['hora_reserva'],
                $datos['num_personas'],
                $cambios,
                true  // editado por admin
            );
        }

        header('Location: dashboard.php?msg=editada');
        exit;
    }
}

$tituloPagina = 'Editar reserva (admin)';
require '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="mb-1">Editar reserva</h2>
        <p class="text-muted">Cliente: <strong><?= htmlspecialchars($reserva['nombre_usuario']) ?></strong></p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Personas</label>
                            <input type="number" name="num_personas" class="form-control"
                                   min="1" max="20" required
                                   value="<?= htmlspecialchars($datos['num_personas']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_reserva" class="form-control" required
                                   value="<?= htmlspecialchars($datos['fecha_reserva']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora_reserva" class="form-control" required
                                   value="<?= htmlspecialchars($datos['hora_reserva']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">¿Lleva bebés?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="bebes"
                                       id="bebesNo" value="no"
                                       <?= $datos['bebes'] !== 'si' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bebesNo">No</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="bebes"
                                       id="bebesSi" value="si"
                                       <?= $datos['bebes'] === 'si' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bebesSi">Sí</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="campoNumBebes"
                         style="display: <?= $datos['bebes'] === 'si' ? 'block' : 'none' ?>;">
                        <label class="form-label">¿Cuántos bebés?</label>
                        <input type="number" name="num_bebes" class="form-control"
                               min="1" max="10"
                               value="<?= htmlspecialchars($datos['num_bebes'] ?: 1) ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" required
                                   value="<?= htmlspecialchars($datos['telefono']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($datos['email']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activa"    <?= $datos['estado'] === 'activa'    ? 'selected' : '' ?>>Activa</option>
                            <option value="cancelada" <?= $datos['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('input[name="bebes"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const campo = document.getElementById('campoNumBebes');
            campo.style.display = (this.value === 'si') ? 'block' : 'none';
        });
    });
</script>

<?php require '../includes/footer.php'; ?>
