<?php
// Cambia aquí los títulos y los textos cuando agregues las fotos de tus eventos.
$publicaciones = [
    1 => [
        'titulo' => 'Cobertura 01',
        'categoria' => 'Fotografía y video',
        'resumen' => 'Una selección de momentos, encuentros y detalles de esta cobertura.',
        'texto' => 'Cada evento tiene su propia historia. En esta publicación voy a compartir las fotografías y los momentos que formaron parte de esta cobertura.',
    ],
    2 => [
        'titulo' => 'Cobertura 02',
        'categoria' => 'Fotografía y video',
        'resumen' => 'Los protagonistas y los pequeños momentos que hacen especial un evento.',
        'texto' => 'Una galería para volver a los gestos, las personas y los detalles de este encuentro. Las imágenes de esta cobertura estarán disponibles próximamente.',
    ],
    3 => [
        'titulo' => 'Cobertura 03',
        'categoria' => 'Fotografía y video',
        'resumen' => 'Otra historia para contar a través de imágenes.',
        'texto' => 'Este espacio reúne el registro de una nueva cobertura de DM Producciones. Pronto voy a sumar las fotografías del evento.',
    ],
];

function foto_portafolio(string $nombre): ?string
{
    $archivos = glob(__DIR__ . '/public/fotos/' . $nombre . '.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
    return $archivos ? '/fotos/' . rawurlencode(basename($archivos[0])) : null;
}

function fotos_cobertura(int $id): array
{
    $archivos = glob(__DIR__ . '/public/fotos/cobertura-' . $id . '-*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
    natsort($archivos);
    return array_values(array_map(fn(string $archivo): string => '/fotos/' . rawurlencode(basename($archivo)), $archivos));
}

function espacio_foto(string $texto): void
{
    ?>
    <div class="sin-foto"><svg viewBox="0 0 80 60" width="64" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M8 17h16l5-8h22l5 8h16v36H8z"/><circle cx="40" cy="34" r="13"/><circle cx="40" cy="34" r="8"/><path d="M60 24h5"/></svg><span><?= e($texto) ?></span></div>
    <?php
}
