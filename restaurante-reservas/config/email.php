<?php
// ============================================
// config/email.php
// Configuración del servidor de correo (SMTP)
//
// IMPORTANTE: este archivo contiene credenciales.
// Añádelo al .gitignore antes de subir el proyecto
// a un repositorio público.
// ============================================

return [
    // Pon esto a false para volver al "modo log"
    // (los emails se guardan en logs/emails.log y no se envían)
    'habilitado' => true,

    // ---- Datos del servidor SMTP (Gmail) ----
    'host'    => 'smtp.gmail.com',
    'puerto'  => 587,
    'cifrado' => 'tls',                 // 'tls' (puerto 587) o 'ssl' (puerto 465)

    // ---- Credenciales ----
    'usuario' => 'mgarciamarcos95@gmail.com', 
    'clave'   => 'ggzrbqutcouzrrmm',    // App Password (sin espacios)

    // ---- Datos del remitente que verá el destinatario ----
    'remitente'  => 'mgarciamarcos95@gmail.com', 
    'nombre_app' => 'Reservas Restaurante',
];
