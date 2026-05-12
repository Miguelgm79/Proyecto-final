<?php
// ============================================
// index.php
// Página de login (entrada principal)
// ============================================
require_once 'includes/auth.php';
require_once 'config/db.php';

// Si ya está logueado, redirigir según rol
if (estaLogueado()) {
    header('Location: ' . dashboardSegunRol());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['password'] ?? '';

    if ($email === '' || $clave === '') {
        $error = 'Debes rellenar todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($clave, $user['password'])) {
            $_SESSION['usuario_id']   = $user['id'];
            $_SESSION['nombre']       = $user['nombre'];
            $_SESSION['email']        = $user['email'];
            $_SESSION['rol']          = $user['rol'];
            $_SESSION['bar_asignado'] = $user['bar_asignado'];

            header('Location: ' . dashboardSegunRol());
            exit;
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
}

$tituloPagina = 'Iniciar sesión';
$mostrarNav   = false;
require 'includes/header.php';
?>

<div class="card card-login shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <img src="assets/logo.svg" alt="Reservas Restaurante" style="max-width: 220px; height: auto;">
        </div>

        <h5 class="text-center text-muted fw-normal mb-4">Iniciar sesión</h5>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <hr class="my-4">

        <p class="text-center mb-0">
            ¿No tienes cuenta?
            <a href="registro.php">Regístrate aquí</a>
        </p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
