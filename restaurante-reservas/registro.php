<?php
// ============================================
// registro.php
// Registro de usuarios nuevos (solo rol 'usuario')
// Los admins se crean a mano en phpMyAdmin
// ============================================
require_once 'includes/auth.php';
require_once 'config/db.php';

if (estaLogueado()) {
    header('Location: index.php');
    exit;
}

$errores = [];
$datos = ['nombre' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['email']  = trim($_POST['email'] ?? '');
    $clave           = $_POST['password'] ?? '';
    $claveRep        = $_POST['password2'] ?? '';

    if ($datos['nombre'] === '')                        $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';
    if (strlen($clave) < 6)                             $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($clave !== $claveRep)                           $errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        // Comprobar si ya existe el email
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$datos['email']]);

        if ($stmt->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese email.';
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol)
                 VALUES (?, ?, ?, 'usuario')"
            );
            $stmt->execute([$datos['nombre'], $datos['email'], $hash]);

            $_SESSION['mensaje_exito'] = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
            header('Location: index.php');
            exit;
        }
    }
}

$tituloPagina = 'Crear cuenta';
$mostrarNav   = false;
require 'includes/header.php';
?>

<div class="card card-login shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <img src="assets/logo.svg" alt="Reservas Restaurante" style="max-width: 220px; height: auto;">
        </div>

        <h5 class="text-center text-muted fw-normal mb-4">Crear cuenta</h5>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                       value="<?= htmlspecialchars($datos['nombre']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($datos['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
                <div class="form-text">Mínimo 6 caracteres.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Repetir contraseña</label>
                <input type="password" name="password2" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
        </form>

        <hr class="my-4">

        <p class="text-center mb-0">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a>
        </p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
