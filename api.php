<?php
/**
 * OmniDownloader — Video Info API
 *
 * Returns JSON metadata (title, thumbnail, duration, uploader) for a given URL.
 * Used by the frontend to show a preview card before the user starts the download.
 *
 * Usage: GET /api.php?url=<encoded_url>
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---- Helpers ------------------------------------------------------------ //

function jsonError(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function isSafeUrl(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) {
        return false;
    }
    $ip = gethostbyname($host);
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

// ---- Input Validation --------------------------------------------------- //

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    jsonError('URL inválida.');
}

if (!isSafeUrl($url)) {
    jsonError('URL não permitida.');
}

// ---- Fetch Metadata via yt-dlp ------------------------------------------ //

$escapedUrl = escapeshellarg($url);
$cmd        = "yt-dlp --no-playlist --dump-single-json --no-download {$escapedUrl} 2>&1";

exec($cmd, $outputLines, $returnCode);

if ($returnCode !== 0) {
    jsonError('Não foi possível obter informações do vídeo.', 422);
}

// yt-dlp may output log lines before the JSON; find the first JSON object line
$jsonLine = '';
foreach ($outputLines as $line) {
    if (str_starts_with(ltrim($line), '{')) {
        $jsonLine = $line;
        break;
    }
}

if ($jsonLine === '') {
    jsonError('Resposta inesperada do servidor.', 500);
}

$data = json_decode($jsonLine, true);

if (!is_array($data)) {
    jsonError('Não foi possível processar as informações do vídeo.', 500);
}

// ---- Return Metadata ---------------------------------------------------- //

echo json_encode([
    'title'     => $data['title']     ?? '',
    'thumbnail' => $data['thumbnail'] ?? '',
    'duration'  => isset($data['duration']) ? (int) $data['duration'] : 0,
    'uploader'  => $data['uploader']  ?? '',
    'extractor' => $data['extractor'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
