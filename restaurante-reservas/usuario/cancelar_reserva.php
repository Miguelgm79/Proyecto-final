<?php
// ============================================
// usuario/cancelar_reserva.php
// Cancela una reserva propia (estado -> 'cancelada')
// ============================================
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/email.php';
requiereUsuario();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Comprobar que la reserva es del usuario y está activa
$stmt = $pdo->prepare("
    SELECT r.*, b.nombre AS nombre_bar
    FROM reservas r
    INNER JOIN bares b ON b.id = r.id_bar
    WHERE r.id = ? AND r.id_usuario = ? AND r.estado = 'activa'
");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: dashboard.php');
    exit;
}

// Cancelar (soft delete con cambio de estado)
$stmt = $pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
$stmt->execute([$id]);

// Enviar email de notificación
emailReservaCancelada(
    $reserva['email'],
    $_SESSION['nombre'],
    $reserva['nombre_bar'],
    date('d/m/Y', strtotime($reserva['fecha_reserva'])),
    substr($reserva['hora_reserva'], 0, 5)
);

header('Location: dashboard.php?msg=cancelada');
exit;
