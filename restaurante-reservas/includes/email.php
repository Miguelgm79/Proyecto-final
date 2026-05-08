<?php
// ============================================
// includes/email.php
// Envío de emails con PHPMailer
// ============================================

// --- Carga de PHPMailer ---
// Si has usado Composer:
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Si lo descargaste a mano en lib/PHPMailer/:
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================
// Carga de la configuración
// ============================================
function configEmail() {
    static $config = null;
    if ($config === null) {
        $f = __DIR__ . '/../config/email.php';
        $config = file_exists($f) ? require $f : ['habilitado' => false];
    }
    return $config;
}

// ============================================
// Función central de envío
// ============================================
function enviarEmail($destinatario, $asunto, $cuerpoHtml, $cuerpoTexto = '') {
    $config = configEmail();

    // Si no hay config o está desactivada -> modo log
    if (empty($config['habilitado'])) {
        return guardarEnLog($destinatario, $asunto, $cuerpoTexto ?: strip_tags($cuerpoHtml));
    }

    $mail = new PHPMailer(true); // true = lanza excepciones si falla

    try {
        // --- Servidor SMTP ---
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['usuario'];
        $mail->Password   = $config['clave'];
        $mail->SMTPSecure = ($config['cifrado'] === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['puerto'];
        $mail->CharSet    = 'UTF-8';

        // --- Remitente y destinatario ---
        $mail->setFrom($config['remitente'], $config['nombre_app']);
        $mail->addAddress($destinatario);

        // --- Contenido ---
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTexto ?: strip_tags($cuerpoHtml);

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Si el envío falla guardamos en log para no perder la notificación
        error_log('[EMAIL ERROR] ' . $mail->ErrorInfo);
        guardarEnLog($destinatario, $asunto, $cuerpoTexto ?: strip_tags($cuerpoHtml));
        return false;
    }
}

// ============================================
// Modo log (fallback / desarrollo sin SMTP)
// ============================================
function guardarEnLog($destinatario, $asunto, $mensaje) {
    $logFile = __DIR__ . '/../logs/emails.log';
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0777, true);
    }
    $log  = "==============================\n";
    $log .= '[' . date('Y-m-d H:i:s') . "] Para: $destinatario\n";
    $log .= "Asunto: $asunto\n------------------------------\n";
    $log .= $mensaje . "\n\n";
    @file_put_contents($logFile, $log, FILE_APPEND);
    return true;
}

// ============================================
// Plantilla HTML genérica con los colores del logo
// ============================================
function plantillaEmail($titulo, $contenido) {
    return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#FCF5E8;font-family:Georgia,serif;color:#1F2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FCF5E8;">
    <tr><td align="center" style="padding:30px 15px;">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0"
             style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
        <tr><td style="background:#C8334F;padding:22px 30px;color:#FBE9D0;
                       font-size:22px;font-weight:500;letter-spacing:0.5px;">
          Reservas Restaurante
        </td></tr>
        <tr><td style="padding:30px;">
          <h2 style="margin:0 0 18px;font-size:20px;color:#1F2937;">'
            . htmlspecialchars($titulo) . '</h2>
          ' . $contenido . '
        </td></tr>
        <tr><td style="background:#FBE9D0;padding:14px 30px;
                       font-size:12px;color:#8B6F47;font-family:Arial,sans-serif;">
          Este correo se ha enviado automáticamente. No respondas a este mensaje.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';
}

// Tabla bonita para los detalles de la reserva (se usa dentro de la plantilla)
function tablaDetalles(array $filas) {
    $html = '<table cellpadding="8" cellspacing="0" style="margin:18px 0;
             border:1px solid #eee;border-collapse:collapse;
             font-family:Arial,sans-serif;font-size:14px;width:100%;">';
    foreach ($filas as $clave => $valor) {
        $html .= '<tr>';
        $html .= '<td style="background:#FBE9D0;width:35%;"><strong>'
              . htmlspecialchars($clave) . '</strong></td>';
        $html .= '<td>' . htmlspecialchars($valor) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

// ============================================
// Email de confirmación al crear una reserva
// ============================================
function emailReservaCreada($email, $nombre, $bar, $fecha, $hora, $personas) {
    $detalles = tablaDetalles([
        'Restaurante' => $bar,
        'Fecha'       => $fecha,
        'Hora'        => $hora,
        'Personas'    => (string)$personas,
    ]);

    $contenido = '
        <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu reserva se ha creado correctamente. Estos son los detalles:</p>
        ' . $detalles . '
        <p>Te esperamos. Si necesitas modificar o cancelar la reserva,
           puedes hacerlo desde tu panel de usuario.</p>
        <p style="margin-top:25px;color:#6c757d;font-size:13px;">¡Buen provecho!</p>
    ';

    $textoPlano  = "Hola $nombre,\n\nTu reserva se ha creado correctamente.\n\n";
    $textoPlano .= "Restaurante: $bar\nFecha: $fecha\nHora: $hora\nPersonas: $personas\n\n";
    $textoPlano .= "Te esperamos.";

    return enviarEmail(
        $email,
        "Confirmación de reserva - $bar",
        plantillaEmail('Reserva confirmada', $contenido),
        $textoPlano
    );
}

// ============================================
// Email cuando el usuario cancela una reserva
// ============================================
function emailReservaCancelada($email, $nombre, $bar, $fecha, $hora) {
    $detalles = tablaDetalles([
        'Restaurante' => $bar,
        'Fecha'       => $fecha,
        'Hora'        => $hora,
    ]);

    $contenido = '
        <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
        <p>Tu reserva ha sido <strong>cancelada</strong> correctamente.</p>
        ' . $detalles . '
        <p>Esperamos verte en otra ocasión.</p>
    ';

    $textoPlano  = "Hola $nombre,\n\nTu reserva ha sido cancelada.\n\n";
    $textoPlano .= "Restaurante: $bar\nFecha: $fecha\nHora: $hora\n\n";
    $textoPlano .= "Esperamos verte en otra ocasión.";

    return enviarEmail(
        $email,
        "Cancelación de reserva - $bar",
        plantillaEmail('Reserva cancelada', $contenido),
        $textoPlano
    );
}