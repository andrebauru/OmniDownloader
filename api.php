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

function isYoutubeUrl(string $url): bool
{
    $u = strtolower($url);
    return str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be');
}

function getConfiguredCookieArgs(): array
{
    $cookiesFromBrowser = trim((string) getenv('YTDLP_COOKIES_FROM_BROWSER'));
    if ($cookiesFromBrowser !== '') {
        return ['--cookies-from-browser', $cookiesFromBrowser];
    }

    $cookieFileEnv = trim((string) getenv('YTDLP_COOKIES_FILE'));
    if ($cookieFileEnv !== '' && is_file($cookieFileEnv)) {
        return ['--cookies', $cookieFileEnv];
    }

    $localCookieFile = __DIR__ . DIRECTORY_SEPARATOR . 'cookies.txt';
    if (is_file($localCookieFile)) {
        return ['--cookies', $localCookieFile];
    }

    return [];
}

function getAutomaticBrowserCookieArgSets(): array
{
    return [
        ['--cookies-from-browser', 'chrome'],
        ['--cookies-from-browser', 'edge'],
        ['--cookies-from-browser', 'firefox'],
        ['--cookies-from-browser', 'brave'],
    ];
}

function isAntiBotOutput(array $outputLines): bool
{
    $detail = mb_strtolower(implode(' ', array_slice($outputLines, -12)));
    $detail = str_replace(["’", "\u{2019}"], "'", $detail);

    return str_contains($detail, "sign in to confirm you're not a bot")
        || str_contains($detail, 'please sign in')
        || str_contains($detail, 'use --cookies-from-browser')
        || str_contains($detail, 'use --cookies for the authentication')
        || str_contains($detail, 'could not find')
        || str_contains($detail, 'cookies database');
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

$args = [
    'yt-dlp',
    '--no-playlist',
    '--no-warnings',
    '--socket-timeout', '20',
    '--retries', '3',
    '--dump-single-json',
    '--no-download',
];

if (isYoutubeUrl($url)) {
    $args[] = '--extractor-args';
    $args[] = 'youtube:player_client=android,web';
}

$args[] = $url;

$isYoutube = isYoutubeUrl($url);
$attempts = [$args];

if ($isYoutube) {
    $configuredCookieArgs = getConfiguredCookieArgs();
    if (!empty($configuredCookieArgs)) {
        $attempts[] = array_merge($args, $configuredCookieArgs);
    }
    foreach (getAutomaticBrowserCookieArgSets() as $cookieArgSet) {
        $attempts[] = array_merge($args, $cookieArgSet);
    }
}

$outputLines = [];
$returnCode = 1;

foreach ($attempts as $attemptArgs) {
    $attemptOutput = [];
    $attemptCode = 1;
    $cmd = implode(' ', array_map('escapeshellarg', $attemptArgs)) . ' 2>&1';
    exec($cmd, $attemptOutput, $attemptCode);

    $outputLines = $attemptOutput;
    $returnCode = $attemptCode;

    if ($attemptCode === 0) {
        break;
    }

    if (!$isYoutube || !isAntiBotOutput($attemptOutput)) {
        break;
    }
}

if ($returnCode !== 0) {
    $detail = trim(implode(' ', array_slice($outputLines, -5)));
    $normalizedDetail = str_replace(["’", "\u{2019}"], "'", mb_strtolower($detail));
    if (str_contains($normalizedDetail, "sign in to confirm you're not a bot")) {
        jsonError('YouTube exigiu verificação anti-bot para este vídeo. O servidor tentou cookies automaticamente, mas não conseguiu autenticar.', 429);
    }
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
