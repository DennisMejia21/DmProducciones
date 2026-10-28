<?php
require_once __DIR__ . '/config.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verificar_token(): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('El formulario venció. Recarga la página e inténtalo nuevamente.');
    }
}

function administrador(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /login.php');
        exit;
    }
}

function aviso(string $mensaje): void
{
    $_SESSION['aviso'] = $mensaje;
}

function cabecera(string $titulo): void
{
    $mensaje = $_SESSION['aviso'] ?? '';
    unset($_SESSION['aviso']);
    ?>
    <!doctype html>
    <html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> | DM Producciones</title><link rel="stylesheet" href="/estilos.css"><script src="/app.js" defer></script></head>
    <body><header><a class="marca" href="/">DM Producciones</a><nav><a href="/">Solicitar reserva</a>
    <?php if (!empty($_SESSION['admin'])): ?><a href="/admin.php">Solicitudes</a><form action="/logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><button class="enlace">Salir</button></form>
    <?php else: ?><a href="/login.php">Administración</a><?php endif; ?></nav></header><main>
    <?php if ($mensaje): ?><p class="aviso" role="status"><?= e($mensaje) ?></p><?php endif; ?>
    <?php
}

function pie(): void
{
    echo '</main><footer>DM Producciones · Fotografía y video para tus eventos</footer></body></html>';
}

// Un único bloqueo para la agenda evita que dos confirmaciones simultáneas se superpongan.
function con_agenda(callable $operacion): mixed
{
    $pdo = db();
    if ((int) $pdo->query("SELECT GET_LOCK('dm_agenda', 5)")->fetchColumn() !== 1) {
        throw new RuntimeException('La agenda está ocupada. Inténtalo nuevamente.');
    }
    try {
        $pdo->beginTransaction();
        $resultado = $operacion($pdo);
        $pdo->commit();
        return $resultado;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('dm_agenda')");
    }
}

function ocupada(PDO $pdo, string $fecha, string $inicio, string $fin, int $excluir = 0): bool
{
    $consulta = $pdo->prepare("SELECT id_solicitud FROM solicitudes WHERE fecha_evento = ? AND estado IN ('confirmada', 'realizada') AND hora_inicio < ? AND hora_fin > ? AND id_solicitud <> ? LIMIT 1");
    $consulta->execute([$fecha, $fin, $inicio, $excluir]);
    return (bool) $consulta->fetch();
}

function solicitud(int $id): array|false
{
    $consulta = db()->prepare('SELECT s.*, c.nombre, c.apellido, c.telefono, c.email FROM solicitudes s JOIN clientes c USING (id_cliente) WHERE s.id_solicitud = ?');
    $consulta->execute([$id]);
    return $consulta->fetch();
}

function solicitar(array $datos, array $servicios): int
{
    return con_agenda(function (PDO $pdo) use ($datos, $servicios): int {
        if (ocupada($pdo, $datos['fecha_evento'], $datos['hora_inicio'], $datos['hora_fin'])) {
            throw new DomainException('Ese horario ya está reservado. Prueba con otra fecha u otro horario.');
        }
        $consulta = $pdo->prepare('INSERT INTO clientes (nombre, apellido, telefono, email) VALUES (?, ?, ?, ?)');
        $consulta->execute([$datos['nombre'], $datos['apellido'], $datos['telefono'], $datos['email']]);
        $cliente = $pdo->lastInsertId();
        $consulta = $pdo->prepare('INSERT INTO solicitudes (id_cliente, fecha_evento, hora_inicio, hora_fin, tipo_evento, lugar, ciudad, direccion, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $consulta->execute([$cliente, $datos['fecha_evento'], $datos['hora_inicio'], $datos['hora_fin'], $datos['tipo_evento'], $datos['lugar'], $datos['ciudad'], $datos['direccion'], $datos['observaciones']]);
        $id = (int) $pdo->lastInsertId();
        $consulta = $pdo->prepare('INSERT INTO detalle_solicitud (id_solicitud, id_servicio) VALUES (?, ?)');
        foreach ($servicios as $servicio) {
            $consulta->execute([$id, $servicio]);
        }
        return $id;
    });
}

function validar_solicitud(array $entrada, array $servicios_validos): array
{
    $datos = [];
    $errores = [];
    $campos = ['nombre' => 80, 'apellido' => 80, 'telefono' => 20, 'email' => 150, 'tipo_evento' => 80, 'fecha_evento' => 10, 'hora_inicio' => 5, 'hora_fin' => 5, 'lugar' => 150, 'ciudad' => 100, 'direccion' => 200, 'observaciones' => 1000];
    foreach ($campos as $campo => $limite) {
        $datos[$campo] = is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
        if (($datos[$campo] === '' && $campo !== 'observaciones') || strlen($datos[$campo]) > $limite) {
            $errores[] = 'Completa correctamente el campo ' . str_replace('_', ' ', $campo) . '.';
        }
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    $datos['telefono'] = preg_replace('/[\s()+-]/', '', $datos['telefono']);
    if (!preg_match('/^[1-9][0-9]{7,14}$/', $datos['telefono'])) {
        $errores[] = 'Ingresa un teléfono con código de país, entre 8 y 15 dígitos.';
    }
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $datos['fecha_evento']);
    $hora_valida = fn(string $hora): bool => (bool) preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $hora);
    if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_evento'] || !$hora_valida($datos['hora_inicio']) || !$hora_valida($datos['hora_fin'])) {
        $errores[] = 'Revisa la fecha y las horas del evento.';
    } elseif ($datos['hora_fin'] <= $datos['hora_inicio']) {
        $errores[] = 'La hora de fin debe ser posterior al inicio, en el mismo día.';
    } elseif (new DateTimeImmutable($datos['fecha_evento'] . ' ' . $datos['hora_inicio']) <= new DateTimeImmutable()) {
        $errores[] = 'El evento debe comenzar en una fecha y hora futura.';
    }
    $seleccion = $entrada['servicios'] ?? [];
    if (!is_array($seleccion) || !$seleccion || count($seleccion) > count($servicios_validos)) {
        $errores[] = 'Selecciona al menos un servicio.';
        $seleccion = [];
    }
    $ids = [];
    foreach ($seleccion as $id) {
        if (!is_scalar($id) || !ctype_digit((string) $id) || !in_array((int) $id, $servicios_validos, true)) {
            $errores[] = 'El servicio seleccionado no existe.';
        } else {
            $ids[] = (int) $id;
        }
    }
    return [$datos, array_unique($ids), array_unique($errores)];
}

function cambiar_estado(int $id, string $destino): void
{
    con_agenda(function (PDO $pdo) use ($id, $destino): void {
        $reserva = solicitud($id);
        $transiciones = [
            'pendiente' => ['confirmada', 'rechazada'],
            'confirmada' => ['cancelada', 'realizada'],
        ];
        if (!$reserva || !in_array($destino, $transiciones[$reserva['estado']] ?? [], true)) {
            throw new DomainException('La solicitud ya cambió de estado o la acción no es válida.');
        }
        if ($destino === 'confirmada') {
            if (new DateTimeImmutable($reserva['fecha_evento'] . ' ' . $reserva['hora_inicio']) <= new DateTimeImmutable()) {
                throw new DomainException('No se puede confirmar un evento que ya comenzó.');
            }
            if (ocupada($pdo, $reserva['fecha_evento'], $reserva['hora_inicio'], $reserva['hora_fin'], $id)) {
                throw new DomainException('No se puede confirmar: el horario se superpone con otra reserva confirmada.');
            }
        }
        if ($destino === 'realizada' && new DateTimeImmutable($reserva['fecha_evento'] . ' ' . $reserva['hora_fin']) > new DateTimeImmutable()) {
            throw new DomainException('El evento todavía no terminó.');
        }
        $consulta = $pdo->prepare('UPDATE solicitudes SET estado = ? WHERE id_solicitud = ?');
        $consulta->execute([$destino, $id]);
    });
}
