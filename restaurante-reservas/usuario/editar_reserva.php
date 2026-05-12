<?php
// ============================================
// usuario/editar_reserva.php
// Editar una reserva propia (solo si está activa)
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email.php';
require_once '../includes/reservas.php';
requiereUsuario();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Comprobar que la reserva existe y es del usuario
$stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ? AND id_usuario = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: dashboard.php');
    exit;
}

// No se permite editar reservas canceladas
if ($reserva['estado'] === 'cancelada') {
    header('Location: dashboard.php');
    exit;
}

// Cargar bares
$bares = $pdo->query("SELECT id, nombre FROM bares ORDER BY nombre")->fetchAll();

$errores = [];
$datos = [
    'id_bar'        => $reserva['id_bar'],
    'num_personas'  => $reserva['num_personas'],
    'fecha_reserva' => $reserva['fecha_reserva'],
    'hora_reserva'  => substr($reserva['hora_reserva'], 0, 5),
    'bebes'         => $reserva['bebes'] ? 'si' : 'no',
    'num_bebes'     => $reserva['num_bebes'],
    'telefono'      => $reserva['telefono'],
    'email'         => $reserva['email'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['id_bar']        = (int)($_POST['id_bar'] ?? 0);
    $datos['num_personas']  = (int)($_POST['num_personas'] ?? 0);
    $datos['fecha_reserva'] = trim($_POST['fecha_reserva'] ?? '');
    $datos['hora_reserva']  = trim($_POST['hora_reserva'] ?? '');
    $datos['bebes']         = $_POST['bebes'] ?? 'no';
    $datos['num_bebes']     = (int)($_POST['num_bebes'] ?? 0);
    $datos['telefono']      = trim($_POST['telefono'] ?? '');
    $datos['email']         = trim($_POST['email'] ?? '');

    if ($datos['id_bar'] <= 0)          $errores[] = 'Debes elegir un restaurante.';
    if ($datos['num_personas'] < 1)     $errores[] = 'El número de personas debe ser al menos 1.';
    if ($datos['fecha_reserva'] === '') $errores[] = 'La fecha es obligatoria.';
    if ($datos['hora_reserva'] === '')  $errores[] = 'La hora es obligatoria.';
    if ($datos['telefono'] === '')      $errores[] = 'El teléfono es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';

    $tieneBebes = ($datos['bebes'] === 'si');
    if ($tieneBebes && $datos['num_bebes'] < 1) {
        $errores[] = 'Si llevas bebés, indica cuántos.';
    }

    // Comprobar que el día elegido no esté completo (excluyendo esta misma reserva)
    if (empty($errores) && !diaTieneSitio($pdo, $datos['id_bar'], $datos['fecha_reserva'], $id)) {
        $errores[] = 'Ese día ese restaurante ya tiene el máximo de '
                   . LIMITE_RESERVAS_DIARIAS . ' reservas. Por favor, elige otro día.';
    }

    if (empty($errores)) {
        // Detectar qué campos han cambiado antes de actualizar
        $cambios = detectarCambios($reserva, $datos, $pdo);

        $stmt = $pdo->prepare("
            UPDATE reservas SET
                id_bar = ?, num_personas = ?, fecha_reserva = ?, hora_reserva = ?,
                bebes = ?, num_bebes = ?, telefono = ?, email = ?
            WHERE id = ? AND id_usuario = ?
        ");
        $stmt->execute([
            $datos['id_bar'],
            $datos['num_personas'],
            $datos['fecha_reserva'],
            $datos['hora_reserva'],
            $tieneBebes ? 1 : 0,
            $tieneBebes ? $datos['num_bebes'] : 0,
            $datos['telefono'],
            $datos['email'],
            $id,
            $_SESSION['usuario_id'],
        ]);

        // Enviar email solo si realmente hubo cambios
        if (!empty($cambios)) {
            $stmt = $pdo->prepare("SELECT nombre FROM bares WHERE id = ?");
            $stmt->execute([$datos['id_bar']]);
            $nombreBar = $stmt->fetchColumn();

            emailReservaEditada(
                $datos['email'],
                $_SESSION['nombre'],
                $nombreBar,
                date('d/m/Y', strtotime($datos['fecha_reserva'])),
                $datos['hora_reserva'],
                $datos['num_personas'],
                $cambios,
                false  // no es admin quien edita
            );
        }

        header('Location: dashboard.php?msg=editada');
        exit;
    }
}

$tituloPagina = 'Editar reserva';
require '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="mb-4">Editar reserva</h2>

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

                    <div class="mb-3">
                        <label class="form-label">Restaurante</label>
                        <select name="id_bar" class="form-select" required>
                            <?php foreach ($bares as $bar): ?>
                                <option value="<?= (int)$bar['id'] ?>"
                                    <?= $datos['id_bar'] == $bar['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bar['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Número de personas</label>
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
                        <label class="form-label">¿Llevas bebés?</label>
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
                            <label class="form-label">Teléfono de contacto</label>
                            <input type="tel" name="telefono" class="form-control" required
                                   value="<?= htmlspecialchars($datos['telefono']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email para confirmación</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($datos['email']) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
                        <button type="submit" class="btn btn-primary" id="btnEnviar">Guardar cambios</button>
                    </div>

                    <!-- Aviso de disponibilidad (lo rellena el JS) -->
                    <div class="alert mt-3" id="avisoDisponibilidad" style="display: none;" role="alert"></div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const RESERVA_ACTUAL_ID = <?= (int)$id ?>;

    document.querySelectorAll('input[name="bebes"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const campo = document.getElementById('campoNumBebes');
            campo.style.display = (this.value === 'si') ? 'block' : 'none';
        });
    });

    // ====================================================
    // Comprobación de disponibilidad del día (AJAX)
    // ====================================================
    function comprobarDisponibilidad() {
        const selectBar = document.querySelector('select[name="id_bar"]');
        const inputFecha = document.querySelector('input[name="fecha_reserva"]');
        const aviso = document.getElementById('avisoDisponibilidad');
        const btn = document.getElementById('btnEnviar');

        const idBar = selectBar.value;
        const fecha = inputFecha.value;

        if (!idBar || !fecha) {
            aviso.style.display = 'none';
            btn.disabled = false;
            return;
        }

        const url = '../ajax/disponibilidad.php'
                  + '?id_bar=' + encodeURIComponent(idBar)
                  + '&fecha='  + encodeURIComponent(fecha)
                  + '&excluir=' + RESERVA_ACTUAL_ID;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.error) return;
                aviso.style.display = 'block';

                if (!data.disponible) {
                    aviso.className = 'alert alert-danger mt-3';
                    aviso.textContent = 'Lo sentimos, ese día este restaurante ya tiene el máximo de '
                                      + data.limite + ' reservas. Por favor, elige otro día.';
                    btn.disabled = true;
                    inputFecha.focus();
                } else {
                    const restantes = data.limite - data.total;
                    aviso.className = 'alert alert-success mt-3';
                    aviso.textContent = 'Día disponible. Quedan ' + restantes
                                      + ' plaza(s) libre(s) para esa fecha.';
                    btn.disabled = false;
                }
            })
            .catch(err => console.error('Error consultando disponibilidad:', err));
    }

    document.querySelector('select[name="id_bar"]').addEventListener('change', comprobarDisponibilidad);
    document.querySelector('input[name="fecha_reserva"]').addEventListener('change', comprobarDisponibilidad);
</script>

<?php require '../includes/footer.php'; ?>
