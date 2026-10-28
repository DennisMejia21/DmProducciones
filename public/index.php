<?php
require_once __DIR__ . '/../funciones.php';
$servicios = db()->query('SELECT * FROM servicios ORDER BY id_servicio')->fetchAll();
$datos = [];
$seleccion = [];
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    [$datos, $seleccion, $errores] = validar_solicitud($_POST, array_column($servicios, 'id_servicio'));
    if (!$errores) {
        try {
            $id = solicitar($datos, $seleccion);
            aviso("Recibimos tu solicitud #$id. Quedó pendiente de revisión. La reserva estará asegurada cuando la confirmemos.");
            header('Location: /');
            exit;
        } catch (DomainException $error) {
            $errores[] = $error->getMessage();
        }
    }
    if ($errores) {
        http_response_code(422);
    }
}
cabecera('Solicitar reserva');
?>
<section class="presentacion"><p class="etiqueta">FOTOGRAFÍA Y VIDEO</p><h1>Contanos sobre tu próximo evento</h1><p>Completa tus datos y te contactaremos para confirmar la disponibilidad y los detalles de la cobertura.</p></section>
<?php if ($errores): ?><div class="errores" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="tarjeta" id="reserva">
<input type="hidden" name="csrf" value="<?= e(token()) ?>">
<h2>Datos de contacto</h2><div class="campos">
<?php foreach (['nombre' => 'Nombre', 'apellido' => 'Apellido', 'telefono' => 'Teléfono con código de país', 'email' => 'Correo electrónico'] as $campo => $label): ?>
<label><?= e($label) ?><input name="<?= e($campo) ?>" type="<?= $campo === 'email' ? 'email' : ($campo === 'telefono' ? 'tel' : 'text') ?>" value="<?= e($datos[$campo] ?? '') ?>" maxlength="<?= $campo === 'email' ? 150 : ($campo === 'telefono' ? 20 : 80) ?>" required></label>
<?php endforeach; ?></div><p class="ayuda">Para WhatsApp, incluye el código de país. Ejemplo de Argentina: 5491123456789.</p>
<h2>El evento</h2><div class="campos">
<label>Tipo de evento<select name="tipo_evento" required><option value="">Seleccionar</option><?php foreach (['Casamiento', 'Cumpleaños', 'Quince años', 'Evento empresarial', 'Otro'] as $tipo): ?><option <?= ($datos['tipo_evento'] ?? '') === $tipo ? 'selected' : '' ?>><?= e($tipo) ?></option><?php endforeach; ?></select></label>
<label>Fecha<input type="date" name="fecha_evento" min="<?= date('Y-m-d') ?>" value="<?= e($datos['fecha_evento'] ?? '') ?>" required></label>
<label>Hora de inicio<input type="time" name="hora_inicio" value="<?= e($datos['hora_inicio'] ?? '') ?>" required></label>
<label>Hora de fin<input type="time" name="hora_fin" value="<?= e($datos['hora_fin'] ?? '') ?>" required></label>
<label>Nombre del lugar<input name="lugar" maxlength="150" value="<?= e($datos['lugar'] ?? '') ?>" required></label>
<label>Ciudad<input name="ciudad" maxlength="100" value="<?= e($datos['ciudad'] ?? '') ?>" required></label>
<label class="ancho">Dirección<input name="direccion" maxlength="200" value="<?= e($datos['direccion'] ?? '') ?>" required></label></div>
<p class="ayuda">Indica el horario completo de cobertura. Por ahora, cada solicitud debe comenzar y terminar el mismo día.</p>
<fieldset><legend>Servicios</legend><?php foreach ($servicios as $servicio): ?><label class="opcion"><input type="checkbox" name="servicios[]" value="<?= $servicio['id_servicio'] ?>" <?= in_array($servicio['id_servicio'], $seleccion, true) ? 'checked' : '' ?>><span><strong><?= e($servicio['nombre']) ?></strong> · $<?= number_format((float) $servicio['precio'], 0, ',', '.') ?><small><?= e($servicio['descripcion']) ?></small></span></label><?php endforeach; ?></fieldset>
<label>Observaciones (opcional)<textarea name="observaciones" maxlength="1000" rows="3"><?= e($datos['observaciones'] ?? '') ?></textarea></label>
<p class="ayuda">Los precios son orientativos en pesos argentinos. Enviar el formulario no confirma la reserva. Tus datos se utilizarán para gestionar esta solicitud.</p>
<button type="submit">Enviar solicitud</button>
</form>
<?php pie(); ?>
