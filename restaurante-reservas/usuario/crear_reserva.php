<?php
// ============================================
// usuario/crear_reserva.php
// Formulario para crear una nueva reserva
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email.php';
requiereUsuario();

// Cargar bares para el desplegable
$bares = $pdo->query("SELECT id, nombre FROM bares ORDER BY nombre")->fetchAll();

$errores = [];
$datos = [
    'id_bar'        => '',
    'num_personas'  => 2,
    'fecha_reserva' => '',
    'hora_reserva'  => '',
    'bebes'         => 'no',
    'num_bebes'     => 0,
    'telefono'      => '',
    'email'         => $_SESSION['email'] ?? '',
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

    if ($datos['id_bar'] <= 0)        $errores[] = 'Debes elegir un restaurante.';
    if ($datos['num_personas'] < 1)   $errores[] = 'El número de personas debe ser al menos 1.';
    if ($datos['fecha_reserva'] === '') $errores[] = 'La fecha es obligatoria.';
    elseif (strtotime($datos['fecha_reserva']) < strtotime(date('Y-m-d'))) {
        $errores[] = 'La fecha no puede ser anterior a hoy.';
    }
    if ($datos['hora_reserva'] === '')  $errores[] = 'La hora es obligatoria.';
    if ($datos['telefono'] === '')      $errores[] = 'El teléfono es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';

    // Si marcó que sí hay bebés, debe indicar al menos 1
    $tieneBebes = ($datos['bebes'] === 'si');
    if ($tieneBebes && $datos['num_bebes'] < 1) {
        $errores[] = 'Si llevas bebés, indica cuántos.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("
            INSERT INTO reservas
                (id_usuario, id_bar, num_personas, fecha_reserva, hora_reserva,
                 bebes, num_bebes, telefono, email, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')
        ");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $datos['id_bar'],
            $datos['num_personas'],
            $datos['fecha_reserva'],
            $datos['hora_reserva'],
            $tieneBebes ? 1 : 0,
            $tieneBebes ? $datos['num_bebes'] : 0,
            $datos['telefono'],
            $datos['email'],
        ]);

        // Buscar nombre del bar para el email
        $stmt = $pdo->prepare("SELECT nombre FROM bares WHERE id = ?");
        $stmt->execute([$datos['id_bar']]);
        $nombreBar = $stmt->fetchColumn();

        emailReservaCreada(
            $datos['email'],
            $_SESSION['nombre'],
            $nombreBar,
            date('d/m/Y', strtotime($datos['fecha_reserva'])),
            $datos['hora_reserva'],
            $datos['num_personas']
        );

        header('Location: dashboard.php?msg=creada');
        exit;
    }
}

$tituloPagina = 'Nueva reserva';
require '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="mb-4">Nueva reserva</h2>

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
                            <option value="">-- Elige un restaurante --</option>
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
                                   min="<?= date('Y-m-d') ?>"
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
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Crear reserva</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Mostrar/ocultar campo de "número de bebés" según el radio seleccionado
    document.querySelectorAll('input[name="bebes"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const campo = document.getElementById('campoNumBebes');
            campo.style.display = (this.value === 'si') ? 'block' : 'none';
        });
    });
</script>

<?php require '../includes/footer.php'; ?>
