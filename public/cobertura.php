<?php
require_once __DIR__ . '/../funciones.php';
require_once __DIR__ . '/../portafolio.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$publicacion = $publicaciones[$id ?: 0] ?? null;
if (!$publicacion) {
    http_response_code(404);
    cabecera('Cobertura no encontrada');
    echo '<section class="presentacion"><h1>No encontramos esa cobertura</h1><p>Podés volver al portafolio para ver las publicaciones disponibles.</p><a class="boton" href="/#portafolio">Volver al portafolio</a></section>';
    pie();
    exit;
}
$fotos = fotos_cobertura($id);
cabecera($publicacion['titulo']);
?>
<a class="link-flecha" href="/#portafolio">← Volver al portafolio</a>
<article class="cobertura-completa"><div class="presentacion"><p class="etiqueta"><?= e($publicacion['categoria']) ?></p><h1><?= e($publicacion['titulo']) ?></h1><p><?= e($publicacion['texto']) ?></p></div>
<?php if ($fotos): ?><div class="galeria"><?php foreach ($fotos as $numero => $foto): ?><a href="<?= e($foto) ?>" target="_blank" rel="noopener" aria-label="Abrir foto <?= $numero + 1 ?> de <?= e($publicacion['titulo']) ?>"><img src="<?= e($foto) ?>" alt="<?= e($publicacion['titulo']) ?>, fotografía <?= $numero + 1 ?>" loading="lazy"></a><?php endforeach; ?></div>
<?php else: ?><div class="galeria-vacia tono-<?= $id ?>"><?php espacio_foto('Las fotografías de esta cobertura estarán disponibles próximamente.'); ?></div><?php endif; ?>
</article>
<section class="cierre"><div><h2>¿Preparamos tu próxima cobertura?</h2><p>Contame sobre tu evento y revisamos la disponibilidad.</p></div><a class="boton" href="/reservas.php">Solicitar una reserva</a></section>
<?php pie(); ?>
