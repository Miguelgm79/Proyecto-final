<?php
// ============================================
// superadmin/editar_bar.php
// El superadmin edita los datos de un restaurante
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereSuperadmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM bares WHERE id = ?");
$stmt->execute([$id]);
$bar = $stmt->fetch();
if (!$bar) {
    header('Location: dashboard.php');
    exit;
}

$errores = [];
$datos = [
    'nombre'    => $bar['nombre'],
    'direccion' => $bar['direccion'],
    'telefono'  => $bar['telefono'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']    = trim($_POST['nombre']    ?? '');
    $datos['direccion'] = trim($_POST['direccion'] ?? '');
    $datos['telefono']  = trim($_POST['telefono']  ?? '');

    if ($datos['nombre'] === '') {
        $errores[] = 'El nombre del restaurante es obligatorio.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare(
            "UPDATE bares SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?"
        );
        $stmt->execute([$datos['nombre'], $datos['direccion'], $datos['telefono'], $id]);
        header('Location: dashboard.php?msg=bar_editado');
        exit;
    }
}

$tituloPagina = 'Editar restaurante';
require '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <h2 class="mb-4">Editar restaurante</h2>

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
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="<?= htmlspecialchars($datos['direccion']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?= htmlspecialchars($datos['telefono']) ?>">
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
