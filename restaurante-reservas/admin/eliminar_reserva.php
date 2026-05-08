<?php
// ============================================
// admin/eliminar_reserva.php
// El admin elimina (borra) una reserva de su bar
// (Borrado físico, distinto a la cancelación del usuario)
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
requiereAdmin();

$id = (int)($_GET['id'] ?? 0);
$idBarAdmin = $_SESSION['bar_asignado'] ?? null;

if ($id <= 0 || !$idBarAdmin) {
    header('Location: dashboard.php');
    exit;
}

// Solo se puede borrar si la reserva pertenece al bar del admin
$stmt = $pdo->prepare("DELETE FROM reservas WHERE id = ? AND id_bar = ?");
$stmt->execute([$id, $idBarAdmin]);

header('Location: dashboard.php?msg=eliminada');
exit;
