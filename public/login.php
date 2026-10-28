<?php
require_once __DIR__ . '/../funciones.php';
if (!empty($_SESSION['admin'])) {
    header('Location: /admin.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $usuario = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
    if (($_SESSION['intentos'] ?? 0) >= 5 && time() - ($_SESSION['ultimo_intento'] ?? 0) < 60) {
        http_response_code(429);
        $error = 'Demasiados intentos. Espera un minuto antes de volver a ingresar.';
    } else {
        if (time() - ($_SESSION['ultimo_intento'] ?? 0) >= 60) $_SESSION['intentos'] = 0;
        $consulta = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $consulta->execute([$usuario]);
        $admin = $consulta->fetch();
        if ($admin && password_verify($clave, $admin['contrasena'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = (int) $admin['id_usuario'];
            unset($_SESSION['intentos'], $_SESSION['ultimo_intento']);
            header('Location: /admin.php');
            exit;
        }
        $_SESSION['intentos'] = ($_SESSION['intentos'] ?? 0) + 1;
        $_SESSION['ultimo_intento'] = time();
        http_response_code(401);
        $error = 'Usuario o contraseña incorrectos.';
    }
}
cabecera('Administración');
?>
<section class="tarjeta ingreso"><h1>Administración</h1><p>Ingresa para revisar las solicitudes.</p>
<?php if ($error): ?><p class="errores" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(token()) ?>">
<label>Usuario<input name="usuario" maxlength="80" autocomplete="username" required></label>
<label>Contraseña<input name="clave" type="password" maxlength="72" autocomplete="current-password" required></label>
<button>Ingresar</button></form></section>
<?php pie(); ?>
