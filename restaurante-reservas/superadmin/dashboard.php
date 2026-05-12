<?php
// ============================================
// superadmin/dashboard.php
// Panel del superadmin:
//   - listado de restaurantes (con formulario para crear)
//   - listado de administradores (con formulario para crear)
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereSuperadmin();

$errores = [];

// ============================================
// Procesar POST (crear bar o crear admin)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ----- Crear restaurante -----
    if ($accion === 'crear_bar') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');

        if ($nombre === '') {
            $errores[] = 'El nombre del restaurante es obligatorio.';
        }

        if (empty($errores)) {
            $stmt = $pdo->prepare(
                "INSERT INTO bares (nombre, direccion, telefono) VALUES (?, ?, ?)"
            );
            $stmt->execute([$nombre, $direccion, $telefono]);
            header('Location: dashboard.php?msg=bar_creado');
            exit;
        }
    }

    // ----- Crear administrador -----
    if ($accion === 'crear_admin') {
        $nombre   = trim($_POST['nombre']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $clave    = $_POST['password']      ?? '';
        $idBar    = (int)($_POST['id_bar']  ?? 0);

        if ($nombre === '')                                    $errores[] = 'El nombre es obligatorio.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errores[] = 'El email no es válido.';
        if (strlen($clave) < 6)                                $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($idBar <= 0)                                       $errores[] = 'Tienes que asignar un restaurante.';

        // Comprobar email único
        if (empty($errores)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe un usuario con ese email.';
            }
        }

        if (empty($errores)) {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol, bar_asignado)
                 VALUES (?, ?, ?, 'admin', ?)"
            );
            $stmt->execute([$nombre, $email, $hash, $idBar]);
            header('Location: dashboard.php?msg=admin_creado');
            exit;
        }
    }
}

// ============================================
// Cargar datos para mostrar
// ============================================
// Bares con conteo de admins y reservas (para avisar antes de borrar)
$bares = $pdo->query("
    SELECT b.*,
        (SELECT COUNT(*) FROM usuarios u WHERE u.bar_asignado = b.id AND u.rol = 'admin') AS num_admins,
        (SELECT COUNT(*) FROM reservas r WHERE r.id_bar = b.id) AS num_reservas
    FROM bares b
    ORDER BY b.nombre
")->fetchAll();

// Admins con el nombre de su bar
$admins = $pdo->query("
    SELECT u.id, u.nombre, u.email, u.bar_asignado, b.nombre AS nombre_bar
    FROM usuarios u
    LEFT JOIN bares b ON b.id = u.bar_asignado
    WHERE u.rol = 'admin'
    ORDER BY u.nombre
")->fetchAll();

$tituloPagina = 'Panel general';
require '../includes/header.php';
?>

<h2 class="mb-4">Panel del superadmin</h2>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php
        $mensajes = [
            'bar_creado'      => 'Restaurante creado correctamente.',
            'bar_editado'     => 'Restaurante actualizado correctamente.',
            'bar_eliminado'   => 'Restaurante eliminado (con sus reservas).',
            'admin_creado'    => 'Administrador creado correctamente.',
            'admin_editado'   => 'Administrador actualizado correctamente.',
            'admin_eliminado' => 'Administrador eliminado correctamente.',
        ];
        echo htmlspecialchars($mensajes[$_GET['msg']] ?? 'Operación correcta.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- TARJETA: Restaurantes                       -->
<!-- ============================================ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <strong>Restaurantes</strong>
        <span class="text-muted">(<?= count($bares) ?>)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th class="text-center">Admins</th>
                    <th class="text-center">Reservas</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bares)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay restaurantes todavía.</td></tr>
                <?php else: ?>
                    <?php foreach ($bares as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['nombre']) ?></td>
                            <td><?= htmlspecialchars($b['direccion'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($b['telefono'] ?: '—') ?></td>
                            <td class="text-center"><?= (int)$b['num_admins'] ?></td>
                            <td class="text-center"><?= (int)$b['num_reservas'] ?></td>
                            <td class="text-end">
                                <a href="editar_bar.php?id=<?= (int)$b['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="eliminar.php?tipo=bar&id=<?= (int)$b['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Eliminar este restaurante también borrará sus <?= (int)$b['num_reservas'] ?> reservas y dejará a <?= (int)$b['num_admins'] ?> admin(s) sin restaurante. ¿Continuar?');">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Formulario crear bar -->
    <div class="card-footer bg-light">
        <p class="mb-2"><strong>Añadir restaurante</strong></p>
        <form method="POST" action="" class="row g-2">
            <input type="hidden" name="accion" value="crear_bar">
            <div class="col-md-4">
                <input type="text" name="nombre" class="form-control form-control-sm"
                       placeholder="Nombre" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="direccion" class="form-control form-control-sm"
                       placeholder="Dirección">
            </div>
            <div class="col-md-3">
                <input type="text" name="telefono" class="form-control form-control-sm"
                       placeholder="Teléfono">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Añadir</button>
            </div>
        </form>
    </div>
</div>


<!-- ============================================ -->
<!-- TARJETA: Administradores                    -->
<!-- ============================================ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <strong>Administradores</strong>
        <span class="text-muted">(<?= count($admins) ?>)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Restaurante asignado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No hay administradores todavía.</td></tr>
                <?php else: ?>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nombre']) ?></td>
                            <td><small><?= htmlspecialchars($a['email']) ?></small></td>
                            <td>
                                <?php if ($a['nombre_bar']): ?>
                                    <?= htmlspecialchars($a['nombre_bar']) ?>
                                <?php else: ?>
                                    <span class="text-danger">Sin restaurante</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="editar_admin.php?id=<?= (int)$a['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="eliminar.php?tipo=admin&id=<?= (int)$a['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Seguro que quieres eliminar al administrador <?= htmlspecialchars($a['nombre'], ENT_QUOTES) ?>?');">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Formulario crear admin -->
    <div class="card-footer bg-light">
        <p class="mb-2"><strong>Añadir administrador</strong></p>
        <form method="POST" action="" class="row g-2">
            <input type="hidden" name="accion" value="crear_admin">
            <div class="col-md-3">
                <input type="text" name="nombre" class="form-control form-control-sm"
                       placeholder="Nombre" required>
            </div>
            <div class="col-md-3">
                <input type="email" name="email" class="form-control form-control-sm"
                       placeholder="Email" required>
            </div>
            <div class="col-md-2">
                <input type="password" name="password" class="form-control form-control-sm"
                       placeholder="Contraseña" minlength="6" required>
            </div>
            <div class="col-md-3">
                <select name="id_bar" class="form-select form-select-sm" required>
                    <option value="">-- Restaurante --</option>
                    <?php foreach ($bares as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Añadir</button>
            </div>
        </form>
        <?php if (empty($bares)): ?>
            <small class="text-muted d-block mt-2">
                Crea primero al menos un restaurante para poder asignarle un admin.
            </small>
        <?php endif; ?>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
