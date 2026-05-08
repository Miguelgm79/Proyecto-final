# Reservas Restaurante - Proyecto 2º DAW

Sistema de reservas para restaurantes con dos roles (usuario / admin), hecho en
**PHP + PDO + MySQL** y **Bootstrap 5**.

---

## Estructura del proyecto

```
restaurante-reservas/
├── bdReservas.sql              # Script para crear la BD
├── config/
│   └── db.php                  # Conexión PDO a MySQL
├── includes/
│   ├── auth.php                # Helpers de sesión y permisos
│   ├── header.php              # Cabecera común (Bootstrap + navbar)
│   ├── footer.php              # Pie común
│   └── email.php               # Envío de notificaciones
├── index.php                   # Login
├── registro.php                # Registro de usuarios nuevos
├── logout.php                  # Cerrar sesión
├── usuario/
│   ├── dashboard.php           # Listado de SUS reservas
│   ├── crear_reserva.php       # Formulario nueva reserva
│   ├── editar_reserva.php      # Editar reserva propia
│   └── cancelar_reserva.php    # Marcar como cancelada
└── admin/
    ├── dashboard.php           # Listado con filtros del bar asignado
    ├── editar_reserva.php      # Editar cualquier reserva del bar
    └── eliminar_reserva.php    # Borrar reserva
```

---

## Instalación

### 1. Copiar archivos
Copia toda la carpeta `restaurante-reservas/` dentro de `htdocs/` (XAMPP) o
`www/` (WAMP/Laragon).

### 2. Crear la base de datos
1. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Pestaña **Importar** -> selecciona `bdReservas.sql` -> **Continuar**.
3. Esto creará la base de datos `bdReservas` con las tablas y datos de ejemplo.

### 3. Generar la contraseña del admin de prueba
El SQL incluye un admin con email `admin@rincon.com`. Como el hash que viene
en el script puede no funcionar en tu PHP, lo más seguro es generar uno tú:

Crea un archivo `generar_hash.php` en la raíz con esto:
```php
<?php echo password_hash('admin123', PASSWORD_DEFAULT);
```
Ábrelo en el navegador, copia el hash que sale y en phpMyAdmin ejecuta:
```sql
UPDATE usuarios
SET password = 'PEGA_AQUÍ_EL_HASH'
WHERE email = 'admin@rincon.com';
```
Después borra `generar_hash.php`.

### 4. Comprobar la conexión
Abre `config/db.php` y revisa los datos:
- host: `localhost`
- usuario: `root`
- contraseña: `''` (vacía por defecto en XAMPP)

### 5. Acceder
Ve a `http://localhost/restaurante-reservas/`.

---

## Cómo usar la aplicación

### Como **usuario nuevo**
1. En la pantalla de login pulsa **"Regístrate aquí"**.
2. Crea tu cuenta y vuelve al login.
3. Una vez dentro: crea, edita o cancela tus reservas.

### Como **admin**
- Los admins **NO se pueden registrar desde la web**. Hay que crearlos a mano
  en phpMyAdmin actualizando o insertando una fila en `usuarios` con:
  - `rol = 'admin'`
  - `bar_asignado = id_del_bar_que_gestiona`
- El admin solo verá las reservas del bar que tenga asignado en
  `bar_asignado`.
- Puede filtrar por estado (activas/canceladas), por fecha y por nombre/email
  del cliente.
- Puede editar o eliminar reservas.

### Crear un admin desde phpMyAdmin
```sql
INSERT INTO usuarios (nombre, email, password, rol, bar_asignado)
VALUES (
    'Admin Tapería',
    'admin@taperia.com',
    'HASH_GENERADO_CON_password_hash()',
    'admin',
    2  -- id del bar que gestiona
);
```

---

## Notificaciones por email

En `includes/email.php` hay dos modos:

- **Desarrollo (por defecto):** los emails se escriben en `logs/emails.log`,
  así puedes ver el contenido sin necesidad de configurar SMTP.
- **Producción:** descomenta la parte de `mail()` o instala **PHPMailer** con
  Composer para envíos reales.

El recordatorio "previo a la reserva" no se envía solo: necesita una **tarea
programada** (cron en Linux, Tarea Programada en Windows) que llame a un
script como `cron/recordatorios.php` cada cierto tiempo. Eso queda como
ampliación, ya que la lógica del envío ya está hecha en `emailReservaCreada()`
(se podría reutilizar para mandar el recordatorio).

---

## Tecnologías usadas

- **PHP 7.4+** con PDO y prepared statements.
- **MySQL / MariaDB** vía phpMyAdmin.
- **Bootstrap 5.3** desde CDN.
- Sesiones nativas de PHP para login.
- `password_hash` / `password_verify` para almacenar contraseñas.
