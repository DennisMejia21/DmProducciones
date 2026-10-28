<?php
require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../portafolio.php';
cabecera('Fotografía y video');
$portada = foto_portafolio('portada');
?>
<section class="portada">
    <div class="portada-texto"><p class="etiqueta">DM PRODUCCIONES · FOTOGRAFÍA Y VIDEO</p><h1>Hay momentos que merecen quedarse.</h1><p>Fotografía, video y otra mirada desde el aire. Soy Dennis y te acompaño a guardar la historia de tu próximo evento.</p><div class="acciones"><a class="boton" href="/reservas.php">Contame sobre tu evento</a><a class="link-flecha" href="#portafolio">Ver mis trabajos <span aria-hidden="true">↗</span></a></div></div>
    <div class="portada-imagen"><?php if ($portada): ?><img src="<?= e($portada) ?>" alt="Una cobertura de DM Producciones" fetchpriority="high"><?php else: ?><div class="marca-portada" aria-label="DM Producciones, fotografía y video"><span class="iniciales">DM</span><span>PRODUCCIONES</span><span class="marca-linea">FOTOGRAFÍA / VIDEO / DRONE</span></div><?php endif; ?><span class="sello-portada">Una historia, muchas miradas.</span></div>
</section>

<section class="seccion" id="portafolio" aria-labelledby="titulo-portafolio">
    <div class="titulo-seccion"><div><p class="etiqueta">EL TRABAJO EN IMÁGENES</p><h2 id="titulo-portafolio">Últimas coberturas</h2></div><p>Tres historias para conocer un poco de lo que hago.</p></div>
    <div class="publicaciones">
    <?php foreach ($publicaciones as $id => $publicacion): $fotos = fotos_cobertura($id); ?>
        <article class="publicacion"><a class="publicacion-enlace" href="/cobertura.php?id=<?= $id ?>" aria-labelledby="cobertura-<?= $id ?>">
            <div class="publicacion-foto tono-<?= $id ?>"><?php if ($fotos): ?><img src="<?= e($fotos[0]) ?>" alt="<?= e($publicacion['titulo']) ?>" loading="lazy"><?php else: espacio_foto('Galería próximamente'); endif; ?><span class="numero-foto">0<?= $id ?></span></div>
            <div class="publicacion-texto"><p class="etiqueta"><?= e($publicacion['categoria']) ?></p><h3 id="cobertura-<?= $id ?>"><?= e($publicacion['titulo']) ?></h3><p><?= e($publicacion['resumen']) ?></p><span class="link-flecha">Ver cobertura <span aria-hidden="true">↗</span></span></div>
        </a></article>
    <?php endforeach; ?>
    </div>
</section>

<section class="seccion sobre-mi" id="sobre-mi" aria-labelledby="titulo-sobre-mi">
    <div class="foto-personal"><?php $foto = foto_portafolio('sobre-mi'); if ($foto): ?><img src="<?= e($foto) ?>" alt="Dennis Mejia, fotógrafo de DM Producciones" loading="lazy"><?php else: espacio_foto('Detrás de la cámara'); endif; ?></div>
    <div><p class="etiqueta">SOBRE MÍ</p><h2 id="titulo-sobre-mi">Hola, soy Dennis.</h2><p>Trabajo haciendo coberturas de fotografía y video para eventos. DM Producciones es el espacio donde comparto mi trabajo y organizo cada nueva cobertura.</p><p>Me interesa registrar lo que pasa de forma cercana: las personas, los detalles y esos momentos que a veces pasan rápido. Antes de cada evento, conversamos sobre lo que necesitás para preparar la cobertura.</p><a class="link-flecha" href="/reservas.php">Hablemos de tu idea <span aria-hidden="true">↗</span></a></div>
</section>

<section class="seccion" id="equipo" aria-labelledby="titulo-equipo">
    <div class="titulo-seccion"><div><p class="etiqueta">DETRÁS DE CADA COBERTURA</p><h2 id="titulo-equipo">Equipo de trabajo</h2></div><p>La cobertura se organiza según el tipo de evento y el servicio que elijas.</p></div>
    <?php $foto = foto_portafolio('equipo'); if ($foto): ?><img class="equipo-foto" src="<?= e($foto) ?>" alt="Equipo de trabajo de DM Producciones" loading="lazy"><?php endif; ?>
    <div class="equipo-lista"><article><span class="numero">01</span><h3>Fotografía</h3><p>Retratos, momentos espontáneos y detalles del evento. La planificación empieza con lo que querés recordar.</p></article><article><span class="numero">02</span><h3>Video</h3><p>El movimiento y el sonido también cuentan la historia. Revisamos juntos qué momentos incluir en la filmación.</p></article><article><span class="numero">03</span><h3>Edición</h3><p>Después de la cobertura, llega la selección y edición del material para preparar la entrega.</p></article></div>
</section>

<section class="seccion drone" id="drone" aria-labelledby="titulo-drone">
    <div><p class="etiqueta">OTRO PUNTO DE VISTA</p><h2 id="titulo-drone">Tomas con drone</h2><p>Una mirada desde arriba para mostrar el lugar y el entorno de tu evento. Las tomas aéreas pueden complementar la fotografía y el video de la cobertura.</p><p class="drone-nota">Lo coordinamos previamente según el lugar, el clima y las condiciones para realizar el vuelo.</p><a class="boton" href="/reservas.php?servicio=drone">Consultar por tomas aéreas</a></div>
    <div class="drone-imagen"><?php $foto = foto_portafolio('drone'); if ($foto): ?><img src="<?= e($foto) ?>" alt="Vista aérea registrada con drone" loading="lazy"><?php else: ?><div class="vista-aerea" aria-hidden="true"><span></span><span></span><span></span><span></span></div><p>Tomas aéreas · DM Producciones</p><?php endif; ?></div>
</section>

<section class="seccion preguntas" id="preguntas" aria-labelledby="titulo-preguntas">
    <p class="etiqueta">ANTES DE RESERVAR</p><h2 id="titulo-preguntas">Preguntas frecuentes</h2>
    <details><summary>¿Cómo solicito una cobertura?</summary><p>Completá el formulario con tus datos, la fecha, el horario y los servicios que te interesan. Voy a revisar la solicitud y contactarte para conversar sobre el evento.</p></details>
    <details><summary>¿Enviar el formulario confirma mi reserva?</summary><p>No. La solicitud queda pendiente hasta que la confirme desde la administración. El sistema verifica que el horario no esté ocupado por otra reserva confirmada.</p></details>
    <details><summary>¿Puedo contratar fotografía y video juntos?</summary><p>Sí. Podés elegir la cobertura de fotografía y video en el formulario y agregar en las observaciones los detalles que quieras coordinar.</p></details>
    <details><summary>¿Cómo recibo las fotos y el video?</summary><p>El material se entrega en formato digital. Antes de confirmar la cobertura, acordamos qué incluye el servicio y el plazo de entrega.</p></details>
    <details><summary>¿Todas las coberturas incluyen drone?</summary><p>Las tomas con drone se consultan por separado. Su realización depende de las condiciones del lugar, el clima y la posibilidad de hacer el vuelo.</p></details>
    <details><summary>¿Cómo coordino un cambio o una cancelación?</summary><p>Contactame con los datos de tu solicitud para revisar el cambio. La nueva fecha u horario estará sujeto a disponibilidad.</p></details>
</section>

<section class="cierre"><div><p class="etiqueta">TU PRÓXIMA HISTORIA</p><h2>¿Tenés un evento en mente?</h2><p>Contame la fecha y qué cobertura estás buscando.</p></div><a class="boton" href="/reservas.php">Solicitar una reserva <span aria-hidden="true">↗</span></a></section>
<?php pie(); ?>
