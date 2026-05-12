<?php
// ============================================
// includes/header.php
// Cabecera común para todas las páginas
// Variables esperadas:
//   $tituloPagina (string)  - título de la pestaña
//   $mostrarNav   (bool)    - si mostrar la navbar (false en login/registro)
// ============================================

if (!isset($tituloPagina)) $tituloPagina = 'Reservas Restaurante';
if (!isset($mostrarNav))   $mostrarNav   = true;

require_once __DIR__ . '/auth.php';
$base = rutaBase();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?> - Reservas Restaurante</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f6f8;
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .card-login {
            max-width: 420px;
            margin: 60px auto;
        }
        .table thead th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<?php if ($mostrarNav && estaLogueado()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand py-0" href="<?= dashboardSegunRol() ?>">
            <img src="<?= $base ?>assets/logo-nav.svg" alt="Reservas Restaurante" height="36">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['rol'] === 'usuario'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>usuario/dashboard.php">Mis reservas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>usuario/crear_reserva.php">Nueva reserva</a>
                    </li>
                <?php elseif ($_SESSION['rol'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>admin/dashboard.php">Panel de reservas</a>
                    </li>
                <?php else: /* superadmin */ ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base ?>superadmin/dashboard.php">Panel general</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="navbar-text me-3">
                        <?= htmlspecialchars($_SESSION['nombre']) ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['rol']) ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base ?>logout.php">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<div class="container">
