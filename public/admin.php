<?php
require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../integraciones.php';
administrador();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    try {
        if ($accion === 'whatsapp') {
            $mensaje = enviar_confirmacion($id);
        } else {
            cambiar_estado($id, $accion);
            $mensaje = 'La solicitud quedó ' . $accion . '.';
            if ($accion === 'confirmada') $mensaje .= ' ' . enviar_confirmacion($id);
        }
        aviso($mensaje);
    } catch (DomainException $error) {
        aviso($error->getMessage());
    }
    header('Location: /admin.php');
    exit;
}
$estados = ['pendiente', 'confirmada', 'rechazada', 'cancelada', 'realizada'];
$estado = is_string($_GET['estado'] ?? null) ? $_GET['estado'] : '';
$fecha = is_string($_GET['fecha'] ?? null) ? $_GET['fecha'] : '';
$condiciones = [];
$parametros = [];
if (in_array($estado, $estados, true)) {
    $condiciones[] = 's.estado = ?';
    $parametros[] = $estado;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $condiciones[] = 's.fecha_evento = ?';
    $parametros[] = $fecha;
}
$donde = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
$pagina = max(1, (int) (filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT) ?: 1));
$offset = min($pagina - 1, 1000000) * 20;
$consulta = db()->prepare('SELECT s.*, c.nombre, c.apellido, c.telefono, c.email FROM solicitudes s JOIN clientes c USING (id_cliente)' . $donde . " ORDER BY s.fecha_evento DESC, s.hora_inicio, s.id_solicitud DESC LIMIT 21 OFFSET $offset");
$consulta->execute($parametros);
$solicitudes = $consulta->fetchAll();
$siguiente = count($solicitudes) > 20;
$solicitudes = array_slice($solicitudes, 0, 20);
cabecera('Solicitudes');
?>
<h1>Solicitudes y reservas</h1><p>Revisa los datos y confirma la disponibilidad antes de reservar la cobertura.</p>
<form class="filtros" method="get"><label>Estado<select name="estado"><option value="">Todos</option><?php foreach ($estados as $opcion): ?><option value="<?= $opcion ?>" <?= $estado === $opcion ? 'selected' : '' ?>><?= ucfirst($opcion) ?></option><?php endforeach; ?></select></label><label>Fecha del evento<input type="date" name="fecha" value="<?= e($fecha) ?>"></label><button>Filtrar</button><a href="/admin.php">Limpiar</a></form>
<?php if (!$solicitudes): ?><p class="tarjeta">No hay solicitudes con estos filtros.</p><?php endif; ?>
<?php foreach ($solicitudes as $reserva):
    $detalle = db()->prepare('SELECT se.nombre, se.precio FROM detalle_solicitud d JOIN servicios se USING (id_servicio) WHERE d.id_solicitud = ?');
    $detalle->execute([$reserva['id_solicitud']]);
    $servicios = $detalle->fetchAll();
?>
<article class="tarjeta" id="solicitud-<?= $reserva['id_solicitud'] ?>">
<span class="estado <?= e($reserva['estado']) ?>"><?= ucfirst($reserva['estado']) ?></span>
<h2>#<?= $reserva['id_solicitud'] ?> · <?= e($reserva['nombre'] . ' ' . $reserva['apellido']) ?></h2>
<div class="detalle"><p><strong>Evento:</strong> <?= e($reserva['tipo_evento']) ?></p><p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($reserva['fecha_evento'])) ?> · <?= substr($reserva['hora_inicio'], 0, 5) ?> a <?= substr($reserva['hora_fin'], 0, 5) ?></p>
<p><strong>Teléfono:</strong> <?= e($reserva['telefono']) ?></p><p><strong>Email:</strong> <?= e($reserva['email']) ?></p><p><strong>Lugar:</strong> <?= e($reserva['lugar']) ?></p><p><strong>Dirección:</strong> <?= e($reserva['direccion'] . ', ' . $reserva['ciudad']) ?></p>
<p><strong>Servicios:</strong> <?= e(implode(', ', array_column($servicios, 'nombre'))) ?></p><p><strong>Importe orientativo:</strong> $<?= number_format(array_sum(array_column($servicios, 'precio')), 0, ',', '.') ?></p></div>
<?php if ($reserva['observaciones']): ?><p><strong>Observaciones:</strong> <?= nl2br(e($reserva['observaciones'])) ?></p><?php endif; ?>
<p class="ayuda">Confirmación por WhatsApp: <?= e(str_replace('_', ' ', $reserva['whatsapp_estado'])) ?>.</p>
<div class="acciones">
<?php
$acciones = match ($reserva['estado']) {
    'pendiente' => ['confirmada' => 'Confirmar', 'rechazada' => 'Rechazar'],
    'confirmada' => ['realizada' => 'Marcar realizada', 'cancelada' => 'Cancelar reserva'],
    default => [],
};
foreach ($acciones as $accion => $label): ?>
<form method="post" data-confirmar="<?= e($label) ?>: ¿continuar?"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><input type="hidden" name="id" value="<?= $reserva['id_solicitud'] ?>"><input type="hidden" name="accion" value="<?= $accion ?>"><button class="<?= in_array($accion, ['rechazada', 'cancelada'], true) ? 'peligro' : '' ?>"><?= e($label) ?></button></form>
<?php endforeach; ?>
<a class="boton secundario" href="/clima.php?id=<?= $reserva['id_solicitud'] ?>">Consultar clima</a>
<?php if ($reserva['estado'] === 'confirmada' && in_array($reserva['whatsapp_estado'], ['error', 'no_configurado', 'sin_enviar'], true)): ?>
<form method="post" data-confirmar="Se intentará enviar la confirmación por WhatsApp. ¿Continuar?"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><input type="hidden" name="id" value="<?= $reserva['id_solicitud'] ?>"><input type="hidden" name="accion" value="whatsapp"><button class="secundario">Reintentar WhatsApp</button></form>
<?php endif; ?></div></article>
<?php endforeach; ?>
<div class="acciones"><?php if ($pagina > 1): ?><a href="?<?= e(http_build_query(['estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina - 1])) ?>">Anterior</a><?php endif; ?><span>Página <?= $pagina ?></span><?php if ($siguiente): ?><a href="?<?= e(http_build_query(['estado' => $estado, 'fecha' => $fecha, 'pagina' => $pagina + 1])) ?>">Siguiente</a><?php endif; ?></div>
<?php pie(); ?>
