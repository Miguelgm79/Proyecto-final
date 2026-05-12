<?php
// ============================================
// includes/reservas.php
// Helpers relacionados con las reservas
// ============================================

// Número máximo de reservas activas por día y por restaurante
const LIMITE_RESERVAS_DIARIAS = 6;

/**
 * Cuenta cuántas reservas ACTIVAS hay para un bar en una fecha.
 * Si pasamos $excluirReservaId, esa reserva no se cuenta
 * (útil al editar una reserva existente: no debe contarse a sí misma).
 */
function contarReservasDelDia(PDO $pdo, int $idBar, string $fecha, ?int $excluirReservaId = null): int {
    $sql = "SELECT COUNT(*) FROM reservas
             WHERE id_bar = ? AND fecha_reserva = ? AND estado = 'activa'";
    $params = [$idBar, $fecha];

    if ($excluirReservaId !== null) {
        $sql .= " AND id <> ?";
        $params[] = $excluirReservaId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Devuelve true si ese día todavía tiene sitio en ese bar.
 */
function diaTieneSitio(PDO $pdo, int $idBar, string $fecha, ?int $excluirReservaId = null): bool {
    return contarReservasDelDia($pdo, $idBar, $fecha, $excluirReservaId) < LIMITE_RESERVAS_DIARIAS;
}
