<?php
// ============================================
// includes/auth.php
// Funciones de control de sesión
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprueba si hay un usuario logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

// Obliga a que haya sesión iniciada (si no, redirige al login)
function requiereLogin() {
    if (!estaLogueado()) {
        header('Location: ' . rutaBase() . 'index.php');
        exit;
    }
}

// Obliga a que el usuario sea admin
function requiereAdmin() {
    requiereLogin();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: ' . rutaBase() . 'usuario/dashboard.php');
        exit;
    }
}

// Obliga a que el usuario sea de tipo 'usuario'
function requiereUsuario() {
    requiereLogin();
    if ($_SESSION['rol'] !== 'usuario') {
        header('Location: ' . rutaBase() . 'admin/dashboard.php');
        exit;
    }
}

// Devuelve la ruta base del proyecto (para enlaces relativos)
function rutaBase() {
    // Cuenta cuántos niveles hay desde el script actual hasta la raíz del proyecto
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($script, '/usuario/') !== false || strpos($script, '/admin/') !== false) {
        return '../';
    }
    return '';
}
