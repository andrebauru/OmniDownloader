<?php
/**
 * OmniDownloader — Download Handler
 *
 * Accepts a URL and format via POST, invokes yt-dlp to download the media,
 * then streams the resulting file to the browser with Content-Disposition:
 * attachment so the browser shows a native "Save As" dialog.
 *
 * Sets a cookie (fileDownloadToken) that the frontend JavaScript polls for
 * to detect when the download has started and can reset the loading state.
 */

ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// ---- Helpers ------------------------------------------------------------ //

function sendError(string $message, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

function cleanup(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($dir);
}

function isSafeUrl(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) {
        return false;
    }
    $ip = gethostbyname($host);
    // Block private/reserved IP ranges (basic SSRF protection)
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

// ---- Input Validation --------------------------------------------------- //

$url    = isset($_POST['url'])    ? trim($_POST['url'])    : '';
$format = isset($_POST['format']) ? trim($_POST['format']) : 'video';
$token  = isset($_POST['token'])  ? trim($_POST['token'])  : '';

if ($url === '') {
    sendError('URL não informada.');
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    sendError('URL inválida. Verifique o link e tente novamente.');
}

if (!isSafeUrl($url)) {
    sendError('URL não permitida.');
}

if (!in_array($format, ['video', 'mp3'], true)) {
    $format = 'video';
}

// ---- Temp Directory ----------------------------------------------------- //

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omnidownloader_' . uniqid('', true);

if (!mkdir($tmpDir, 0755, true)) {
    sendError('Erro interno: não foi possível criar diretório temporário.', 500);
}

// ---- Build yt-dlp Command ----------------------------------------------- //

$outputTemplate = escapeshellarg($tmpDir . DIRECTORY_SEPARATOR . '%(title).80s.%(ext)s');
$escapedUrl     = escapeshellarg($url);

if ($format === 'mp3') {
    $cmd = "yt-dlp --no-playlist -x --audio-format mp3 --audio-quality 192K"
         . " -o {$outputTemplate} {$escapedUrl} 2>&1";
} else {
    $cmd = "yt-dlp --no-playlist"
         . " -f \"bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best\""
         . " --merge-output-format mp4"
         . " -o {$outputTemplate} {$escapedUrl} 2>&1";
}

// ---- Execute yt-dlp ----------------------------------------------------- //

exec($cmd, $outputLines, $returnCode);

if ($returnCode !== 0) {
    cleanup($tmpDir);
    $detail = implode(' ', array_slice($outputLines, -3));
    sendError("Não foi possível processar o download. Verifique se a URL é válida.\n\nDetalhe: {$detail}", 422);
}

// ---- Locate Output File ------------------------------------------------- //

$files = array_filter(glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [], 'is_file');

if (empty($files)) {
    cleanup($tmpDir);
    sendError('Arquivo não encontrado após o download. Tente novamente.', 500);
}

$filePath = (string) reset($files);
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$mimeType = ($format === 'mp3') ? 'audio/mpeg' : 'video/mp4';

// ---- Set Download Token Cookie ------------------------------------------ //
// The frontend JS polls for this cookie to detect when the download starts.

if ($token !== '') {
    setcookie('fileDownloadToken', $token, [
        'expires'  => time() + 300,
        'path'     => '/',
        'secure'   => false,
        'httponly' => false,
        'samesite' => 'Strict',
    ]);
}

// ---- Stream File to Browser --------------------------------------------- //

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$fp = fopen($filePath, 'rb');
while (!feof($fp) && connection_status() === 0) {
    echo fread($fp, 1_048_576); // 1 MB chunks
    flush();
}
fclose($fp);

// ---- Cleanup ------------------------------------------------------------ //

cleanup($tmpDir);
exit;
