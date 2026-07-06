<?php
/**
 * Script para generar iconos PWA en diferentes tamaños
 * Ejecutar desde la raíz del proyecto: php public/generate-pwa-icons.php
 */

$sourceImage = __DIR__ . '/images/bussines-logo-rounded-white.png';
$outputDir = __DIR__ . '/images/pwa/';

// Tamaños requeridos para PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

if (!file_exists($sourceImage)) {
    die("Error: No se encuentra la imagen fuente en {$sourceImage}\n");
}

if (!extension_loaded('gd')) {
    die("Error: La extensión GD de PHP no está instalada\n");
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$source = imagecreatefrompng($sourceImage);
if (!$source) {
    die("Error: No se pudo cargar la imagen fuente\n");
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

echo "Generando iconos PWA...\n";

foreach ($sizes as $size) {
    $destination = imagecreatetruecolor($size, $size);
    
    // Preservar transparencia
    imagealphablending($destination, false);
    imagesavealpha($destination, true);
    $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
    imagefilledrectangle($destination, 0, 0, $size, $size, $transparent);
    imagealphablending($destination, true);
    
    // Redimensionar
    imagecopyresampled(
        $destination, $source,
        0, 0, 0, 0,
        $size, $size,
        $sourceWidth, $sourceHeight
    );
    
    $outputFile = $outputDir . "icon-{$size}x{$size}.png";
    imagepng($destination, $outputFile, 9);
    imagedestroy($destination);
    
    echo "✓ Creado: icon-{$size}x{$size}.png\n";
}

imagedestroy($source);

echo "\n¡Iconos PWA generados exitosamente!\n";
echo "Los iconos están en: {$outputDir}\n";
