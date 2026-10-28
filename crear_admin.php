<?php
// Se ejecuta desde la terminal, no desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/config.php';
$usuario = getenv('ADMIN_USER') ?: 'admin';
$clave = getenv('ADMIN_PASSWORD') ?: '';
if (strlen($clave) < 10 || strlen($clave) > 72 || strlen($usuario) > 80) {
    fwrite(STDERR, "Configura ADMIN_PASSWORD con entre 10 y 72 caracteres y un usuario de hasta 80.\n");
    exit(1);
}
$consulta = db()->prepare('INSERT IGNORE INTO usuarios (nombre, usuario, contrasena) VALUES (?, ?, ?)');
$consulta->execute(['Administrador', $usuario, password_hash($clave, PASSWORD_DEFAULT)]);
echo $consulta->rowCount() ? "Administrador creado.\n" : "El administrador ya existe; su contraseña no se modificó.\n";
