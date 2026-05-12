<?php
// ============================================
// superadmin/eliminar.php
// Handler único para eliminar restaurantes o administradores
// Uso: eliminar.php?tipo=bar&id=X
//      eliminar.php?tipo=admin&id=X
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereSuperadmin();

$tipo = $_GET['tipo'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

if ($tipo === 'admin') {
    // Solo borrar si realmente es admin (por seguridad, no se cargan superadmins)
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'admin'");
    $stmt->execute([$id]);
    header('Location: dashboard.php?msg=admin_eliminado');
    exit;
}

if ($tipo === 'bar') {
    // ON DELETE CASCADE en reservas borra automáticamente las reservas del bar.
    // ON DELETE SET NULL en usuarios.bar_asignado deja a los admins sin bar.
    $stmt = $pdo->prepare("DELETE FROM bares WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: dashboard.php?msg=bar_eliminado');
    exit;
}

// Tipo desconocido -> volver al dashboard
header('Location: dashboard.php');
exit;
