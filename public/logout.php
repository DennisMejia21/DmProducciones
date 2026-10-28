<?php
require_once __DIR__ . '/../funciones.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
verificar_token();
$_SESSION = [];
session_destroy();
header('Location: /login.php');
