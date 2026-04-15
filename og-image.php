<?php
/**
 * OmniDownloader — Dynamic Preview Image Generator
 * Generates custom Open Graph images for video URLs
 * 
 * Usage: /og-image.php?url=<encoded_url>&title=<encoded_title>&platform=<platform>
 * Returns: PNG image optimized for social media sharing (1200x630)
 * 
 * Cache: Images are cached in temp directory to avoid regenerating same images
 */

header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800'); // Cache por 7 dias
header('X-Content-Type-Options: nosniff');

// Parâmetros
$url = isset($_GET['url']) ? trim($_GET['url']) : '';
$title = isset($_GET['title']) ? trim($_GET['title']) : 'OmniDownloader';
$platform = isset($_GET['platform']) ? trim($_GET['platform']) : 'YouTube';

// Sanitizar
$title = substr(strip_tags($title), 0, 100);
$platform = substr(strip_tags($platform), 0, 30);

// Hash para cache
$cacheKey = md5($url . $title . $platform);
$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omni_og_cache';
$cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.png';

// Verificar cache
if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 604800) {
    readfile($cachePath);
    exit;
}

// Criar diretório de cache se não existir
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Cores por plataforma
$platformColors = [
    'YouTube'   => ['r' => 255, 'g' => 0, 'b' => 0],
    'TikTok'    => ['r' => 0, 'g' => 240, 'b' => 120],
    'Instagram' => ['r' => 245, 'g' => 105, 'b' => 194],
    'Twitter'   => ['r' => 29, 'g' => 161, 'b' => 242],
    'Facebook'  => ['r' => 59, 'g' => 89, 'b' => 152],
    'SoundCloud'=> ['r' => 255, 'g' => 140, 'b' => 0],
];

$bgColor = $platformColors[$platform] ?? $platformColors['YouTube'];

try {
    // Criar imagem
    $img = imagecreatetruecolor(1200, 630);
    
    // Cores
    $bgColorId = imagecolorallocate($img, $bgColor['r'], $bgColor['g'], $bgColor['b']);
    $whiteId = imagecolorallocate($img, 255, 255, 255);
    $darkId = imagecolorallocate($img, 0, 0, 0);
    $accentId = imagecolorallocate($img, 255, 215, 0);
    
    // Preenchimento com gradiente
    for ($y = 0; $y < 630; $y++) {
        $factor = $y / 630;
        $r = intval($bgColor['r'] + (13 - $bgColor['r']) * $factor);
        $g = intval($bgColor['g'] + (71 - $bgColor['g']) * $factor);
        $b = intval($bgColor['b'] + (161 - $bgColor['b']) * $factor);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, 1200, $y, $color);
    }
    
    // Logo/texto no topo
    $fontPath = __DIR__ . '/assets/fonts/arial.ttf';
    if (!file_exists($fontPath)) {
        $fontPath = __DIR__ . '/assets/fonts/DejaVuSans-Bold.ttf';
    }
    
    // Se nenhuma fonte truetype, usar fonte built-in
    if (!file_exists($fontPath)) {
        // Cabeçalho
        imagestring($img, 5, 50, 50, 'OmniDownloader', $whiteId);
        imagestring($img, 4, 50, 150, substr($title, 0, 70), $whiteId);
        imagestring($img, 3, 50, 250, 'Download de ' . $platform, $accentId);
        imagestring($img, 2, 50, 550, 'Rápido • Gratuito • Sem cadastro', $whiteId);
    } else {
        // Com fontes truetype
        imagettftext($img, 60, 0, 50, 80, $whiteId, $fontPath, 'OmniDownloader');
        imagettftext($img, 40, 0, 50, 180, $whiteId, $fontPath, substr($title, 0, 60));
        imagettftext($img, 35, 0, 50, 280, $accentId, $fontPath, 'Download de ' . $platform);
        imagettftext($img, 24, 0, 50, 580, $whiteId, $fontPath, 'Rápido • Gratuito • Sem cadastro');
    }
    
    // Salvar imagem em cache
    imagepng($img, $cachePath, 9);
    imagedestroy($img);
    
    // Enviar imagem
    readfile($cachePath);
    
} catch (Exception $e) {
    // Em caso de erro, enviar og-image padrão
    header('Location: assets/img/og-image.png');
    exit;
}
