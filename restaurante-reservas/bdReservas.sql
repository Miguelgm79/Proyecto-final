-- ============================================
-- Base de datos: bdReservas
-- Sistema de Reservas para Restaurantes
-- ============================================

CREATE DATABASE IF NOT EXISTS bdReservas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bdReservas;

-- ============================================
-- Tabla: bares
-- ============================================
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS bares;

CREATE TABLE bares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(200),
    telefono VARCHAR(20)
) ENGINE=InnoDB;

-- ============================================
-- Tabla: usuarios
-- Rol 'admin' solo se asigna manualmente desde phpMyAdmin
-- bar_asignado: solo se usa para admins (NULL para usuarios normales)
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    bar_asignado INT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bar_asignado) REFERENCES bares(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Tabla: reservas
-- estado: 'activa' o 'cancelada'
-- ============================================
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_bar INT NOT NULL,
    num_personas INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_reserva TIME NOT NULL,
    bebes TINYINT(1) NOT NULL DEFAULT 0,
    num_bebes INT NOT NULL DEFAULT 0,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    estado ENUM('activa', 'cancelada') NOT NULL DEFAULT 'activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_bar) REFERENCES bares(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Datos de ejemplo
-- ============================================
INSERT INTO bares (nombre, direccion, telefono) VALUES
    ('Restaurante El Rincón', 'Calle Mayor, 12 - Madrid', '910000001'),
    ('La Tapería del Centro', 'Av. Andalucía, 45 - Madrid', '910000002'),
    ('Bar Marina', 'Paseo del Mar, 8 - Madrid', '910000003');

-- Usuario admin de ejemplo (contraseña: admin123)
-- Hash generado con password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, email, password, rol, bar_asignado) VALUES
    ('Administrador Rincón', 'admin@rincon.com',
     '$2y$10$keyy9GsibgtekcyZpsueRe2QlA2FEp8dbg.yLuQRdRNlUmjil/OYm',
     'admin', 1);

-- Credenciales de prueba:
--   Email:       admin@rincon.com
--   Contraseña:  admin123
--
-- Si el login no funciona en tu PHP (versiones muy antiguas), genera otro hash:
--   <?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>
-- y luego ejecuta:
--   UPDATE usuarios SET password = 'el_nuevo_hash' WHERE email = 'admin@rincon.com';
