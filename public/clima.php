<?php
require_once __DIR__ . '/../integraciones.php';
administrador();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$reserva = solicitud($id);
if (!$reserva) {
    http_response_code(404);
    exit('La solicitud no existe.');
}
$ciudad = is_string($_GET['ciudad'] ?? null) ? substr(trim($_GET['ciudad']), 0, 100) : $reserva['ciudad'];
$fecha = new DateTimeImmutable($reserva['fecha_evento']);
$hoy = new DateTimeImmutable('today');
$en_rango = $fecha >= $hoy && $fecha <= $hoy->modify('+15 days');
$resultados = [];
$pronostico = null;
$error = '';
if ($en_rango) {
    try {
        $lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lon = filter_var($_GET['lon'] ?? null, FILTER_VALIDATE_FLOAT);
        if (isset($_GET['lat'], $_GET['lon']) && $lat !== false && $lon !== false && abs($lat) <= 90 && abs($lon) <= 180) {
            $respuesta = pedir_json('https://api.open-meteo.com/v1/forecast?' . http_build_query([
                'latitude' => $lat, 'longitude' => $lon,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => date_default_timezone_get(),
                'start_date' => $reserva['fecha_evento'], 'end_date' => $reserva['fecha_evento'],
            ]));
            $pronostico = $respuesta['daily'] ?? null;
            if (!is_array($pronostico) || ($pronostico['time'][0] ?? '') !== $reserva['fecha_evento']) throw new RuntimeException('Sin datos.');
        } else {
            $respuesta = pedir_json('https://geocoding-api.open-meteo.com/v1/search?' . http_build_query(['name' => $ciudad, 'count' => 5, 'language' => 'es', 'format' => 'json']));
            $resultados = $respuesta['results'] ?? [];
        }
    } catch (Throwable $excepcion) {
        $error = 'No pudimos consultar Open-Meteo. Intenta nuevamente más tarde.';
    }
}
cabecera('Clima del evento');
?>
<a href="/admin.php">← Volver a las solicitudes</a><h1>Clima del evento #<?= $id ?></h1>
<p><?= e($reserva['lugar'] . ', ' . $reserva['ciudad']) ?> · <?= $fecha->format('d/m/Y') ?></p>
<?php if (!$en_rango): ?><p class="aviso">El pronóstico solo se consulta desde hoy hasta los próximos 15 días. Para eventos más lejanos, vuelve a revisar cuando se acerque la fecha.</p>
<?php else: ?>
<form method="get" class="filtros"><input type="hidden" name="id" value="<?= $id ?>"><label>Buscar ciudad<input name="ciudad" maxlength="100" value="<?= e($ciudad) ?>" required></label><button>Buscar</button></form>
<?php if ($error): ?><p class="errores" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($pronostico): ?><section class="tarjeta"><h2><?= e(descripcion_clima((int) $pronostico['weather_code'][0])) ?></h2><p>Mínima: <strong><?= e($pronostico['temperature_2m_min'][0] ?? 'Sin datos') ?> °C</strong> · Máxima: <strong><?= e($pronostico['temperature_2m_max'][0] ?? 'Sin datos') ?> °C</strong></p><p>Probabilidad de lluvia: <?= e($pronostico['precipitation_probability_max'][0] ?? 'Sin datos') ?> %</p><p class="ayuda">Coordenadas consultadas: <?= e($lat) ?>, <?= e($lon) ?>. Pronóstico diario, sujeto a cambios.</p></section>
<?php elseif (!$error): ?><h2>Selecciona la ubicación del evento</h2><p class="ayuda">Comprueba la provincia y el país para no confundir ciudades con el mismo nombre.</p><?php if (!$resultados): ?><p>No se encontraron ciudades. Prueba con una ciudad cercana.</p><?php endif; ?>
<?php foreach ($resultados as $lugar): ?><p class="tarjeta"><a href="?<?= e(http_build_query(['id' => $id, 'ciudad' => $ciudad, 'lat' => $lugar['latitude'], 'lon' => $lugar['longitude']])) ?>"><?= e($lugar['name'] . ', ' . ($lugar['admin1'] ?? '') . ', ' . ($lugar['country'] ?? '')) ?></a></p><?php endforeach; ?>
<?php endif; endif; ?>
<p class="ayuda">Datos: <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a>. El clima es una ayuda para planificar; no confirma ni cancela reservas.</p>
<?php pie(); ?>
