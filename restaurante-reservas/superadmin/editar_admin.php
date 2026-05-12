<?php
// ============================================
// superadmin/editar_admin.php
// El superadmin edita un administrador
// (la contraseña solo se cambia si se rellena)
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereSuperadmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Cargar admin
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'admin'");
$stmt->execute([$id]);
$admin = $stmt->fetch();
if (!$admin) {
    header('Location: dashboard.php');
    exit;
}

// Cargar bares para el desplegable
$bares = $pdo->query("SELECT id, nombre FROM bares ORDER BY nombre")->fetchAll();

$errores = [];
$datos = [
    'nombre'       => $admin['nombre'],
    'email'        => $admin['email'],
    'bar_asignado' => $admin['bar_asignado'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']       = trim($_POST['nombre']        ?? '');
    $datos['email']        = trim($_POST['email']         ?? '');
    $datos['bar_asignado'] = (int)($_POST['bar_asignado'] ?? 0);
    $claveNueva            = $_POST['password']           ?? '';

    if ($datos['nombre'] === '')                                $errores[] = 'El nombre es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL))    $errores[] = 'El email no es válido.';
    if ($datos['bar_asignado'] <= 0)                            $errores[] = 'Asigna un restaurante.';
    if ($claveNueva !== '' && strlen($claveNueva) < 6)          $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';

    // Email único (excepto el propio)
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
        $stmt->execute([$datos['email'], $id]);
        if ($stmt->fetch()) {
            $errores[] = 'Ya existe otro usuario con ese email.';
        }
    }

    if (empty($errores)) {
        if ($claveNueva !== '') {
            $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE usuarios
                    SET nombre = ?, email = ?, bar_asignado = ?, password = ?
                  WHERE id = ? AND rol = 'admin'"
            );
            $stmt->execute([$datos['nombre'], $datos['email'], $datos['bar_asignado'], $hash, $id]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE usuarios
                    SET nombre = ?, email = ?, bar_asignado = ?
                  WHERE id = ? AND rol = 'admin'"
            );
            $stmt->execute([$datos['nombre'], $datos['email'], $datos['bar_asignado'], $id]);
        }
        header('Location: dashboard.php?msg=admin_editado');
        exit;
    }
}

$tituloPagina = 'Editar administrador';
require '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="mb-4">Editar administrador</h2>

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
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?= htmlspecialchars($datos['nombre']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($datos['email']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Restaurante asignado</label>
                        <select name="bar_asignado" class="form-select" required>
                            <option value="">-- Elige uno --</option>
                            <?php foreach ($bares as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"
                                    <?= $datos['bar_asignado'] == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                        <div class="form-text">Déjalo vacío si no quieres cambiarla.</div>
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

<?php require '../includes/footer.php'; ?>
