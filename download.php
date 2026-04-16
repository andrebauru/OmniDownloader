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

function isYoutubeUrl(string $url): bool
{
    $u = strtolower($url);
    return str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be');
}

function buildCommand(array $args): string
{
    return implode(' ', array_map('escapeshellarg', $args));
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

function canAccessBrowserCookies(): bool
{
    if (PHP_OS_FAMILY !== 'Windows') {
        $currentUser = trim(shell_exec('whoami') ?? '');
        $scriptDir = __DIR__;
        
        if (in_array($currentUser, ['www-data', 'www', 'httpd', 'apache', 'nginx', '_www', 'nobody'], true)) {
            return false;
        }
        
        if (strpos($scriptDir, '/var/www') === 0 || strpos($scriptDir, '/home/*/public_html') !== false) {
            return false;
        }
    }
    
    return true;
}

function detectInstalledBrowsers(): array
{
    $browsers = ['chrome', 'firefox', 'edge', 'brave'];
    $availableBrowsers = [];

    foreach ($browsers as $browser) {
        $testCmd = 'yt-dlp --cookies-from-browser ' . escapeshellarg($browser) . ' --version 2>&1';
        exec($testCmd, $output, $returnCode);
        $output = implode(' ', $output);

        if (strpos($output, 'could not find') === false) {
            $availableBrowsers[] = $browser;
        }
    }

    if (empty($availableBrowsers)) {
        return ['chrome', 'firefox', 'edge'];
    }

    return $availableBrowsers;
}

function getAutomaticBrowserCookieArgSets(): array
{
    $installedBrowsers = detectInstalledBrowsers();
    
    $installedBrowsers = array_filter(
        $installedBrowsers,
        fn($b) => $b !== 'brave' || isValidBravePath()
    );

    if (empty($installedBrowsers)) {
        return [
            ['--cookies-from-browser', 'chrome'],
            ['--cookies-from-browser', 'firefox'],
            ['--cookies-from-browser', 'edge'],
        ];
    }

    $priority = ['chrome', 'firefox', 'edge', 'brave'];
    $orderedBrowsers = [];
    
    foreach ($priority as $browser) {
        if (in_array($browser, $installedBrowsers, true)) {
            $orderedBrowsers[] = $browser;
        }
    }

    return array_map(
        fn($browser) => ['--cookies-from-browser', $browser],
        $orderedBrowsers
    );
}

function isValidBravePath(): bool
{
    if (PHP_OS_FAMILY === 'Windows') {
        $paths = [
            'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            'C:\\Program Files (x86)\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
    } else {
        $paths = [
            '/opt/brave.com/brave/brave',
            '/usr/bin/brave',
            '/usr/bin/brave-browser',
            '/snap/bin/brave',
        ];
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return true;
            }
        }
    }
    
    return false;
}

function isAntiBotOutput(array $outputLines): bool
{
    $detail = mb_strtolower(implode(' ', array_slice($outputLines, -12)));
    $detail = str_replace(["'", "\u{2019}"], "'", $detail);

    return str_contains($detail, "sign in to confirm you're not a bot")
        || str_contains($detail, 'please sign in')
        || str_contains($detail, 'use --cookies-from-browser')
        || str_contains($detail, 'use --cookies for the authentication')
        || str_contains($detail, 'could not find')
        || str_contains($detail, 'cookies database');
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

$baseArgs = [
    'yt-dlp',
    '--no-playlist',
    '--no-warnings',
    '--newline',
    '--socket-timeout', '20',
    '--retries', '4',
    '--fragment-retries', '4',
    '--extractor-retries', '2',
    '--output', $tmpDir . DIRECTORY_SEPARATOR . '%(title).80s.%(ext)s',
];

if (isYoutubeUrl($url)) {
    $baseArgs[] = '--extractor-args';
    $baseArgs[] = 'youtube:player_client=android,web';
}

if ($format === 'mp3') {
    $baseArgs[] = '-x';
    $baseArgs[] = '--audio-format';
    $baseArgs[] = 'mp3';
    $baseArgs[] = '--audio-quality';
    $baseArgs[] = '192K';
} else {
    $baseArgs[] = '-f';
    $baseArgs[] = 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best';
    $baseArgs[] = '--merge-output-format';
    $baseArgs[] = 'mp4';
}

$baseArgs[] = $url;

// ---- Execute yt-dlp ----------------------------------------------------- //

$isYoutube = isYoutubeUrl($url);
$isInstagram = stripos($url, 'instagram.com') !== false;
$isTwitter = stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false;

$attempts = [$baseArgs];

if ($isYoutube || $isInstagram || $isTwitter) {
    $configuredCookieArgs = getConfiguredCookieArgs();
    if (!empty($configuredCookieArgs)) {
        $attempts[] = array_merge($baseArgs, $configuredCookieArgs);
    }
    
    if (canAccessBrowserCookies()) {
        foreach (getAutomaticBrowserCookieArgSets() as $cookieArgSet) {
            $attempts[] = array_merge($baseArgs, $cookieArgSet);
        }
    }
}

$outputLines = [];
$returnCode = 1;
$lastDetail = '';

foreach ($attempts as $attemptArgs) {
    $attemptOutput = [];
    $attemptCode = 1;
    exec(buildCommand($attemptArgs) . ' 2>&1', $attemptOutput, $attemptCode);

    $outputLines = $attemptOutput;
    $returnCode = $attemptCode;
    $lastDetail = trim(implode(' ', array_slice($outputLines, -5)));

    if ($attemptCode === 0) {
        break;
    }

    $outputText = implode(' ', $attemptOutput);
    if (stripos($outputText, 'could not find') !== false && 
        (stripos($outputText, 'database') !== false || 
         stripos($outputText, 'cookies') !== false)) {
        continue;
    }

    $shouldContinue = false;
    if ($isYoutube) {
        $shouldContinue = isAntiBotOutput($attemptOutput);
    } elseif ($isInstagram || $isTwitter) {
        $shouldContinue = (count($attempts) > 1);
    }
    
    if (!$shouldContinue) {
        break;
    }
}

if ($returnCode !== 0) {
    cleanup($tmpDir);
    // Tentar pegar mais detalhes da saída - até 20 linhas
    $detail = trim(implode(' ', array_slice($outputLines, -20)));
    if ($detail === '' && $lastDetail !== '') {
        $detail = $lastDetail;
    }
    
    // Se ainda estiver muito vazio, mostrar toda a saída com quebra de linha
    if (strlen($detail) < 30 && !empty($outputLines)) {
        $detail = implode("\n", $outputLines);
    }
    
    // Sempre registrar em log para debug
    @file_put_contents(
        sys_get_temp_dir() . '/omnidownloader_error.log',
        date('Y-m-d H:i:s') . " | URL: $url | Return: $returnCode | Detail: " . substr($detail, 0, 200) . "\n",
        FILE_APPEND
    );
    
    $normalizedDetail = str_replace(["'", "\u{2019}"], "'", mb_strtolower($detail));

    if (str_contains($normalizedDetail, "sign in to confirm you're not a bot")) {
        $detectedBrowsers = detectInstalledBrowsers();
        $browserList = !empty($detectedBrowsers) 
            ? implode(', ', array_map('ucfirst', $detectedBrowsers))
            : 'Chrome/Edge/Firefox/Brave';
        
        sendError(
            "O YouTube está exigindo verificação anti-bot para este vídeo.\n"
            . "O servidor já tentou ativar cookies automaticamente ({$browserList}), mas não conseguiu autenticar.\n\n"
            . "Detalhe técnico: {$detail}",
            429
        );
    }

    if ($detail === '') {
        $detail = 'Erro desconhecido ao processar o vídeo. Verifique se a URL é válida.';
    }

    sendError("Não foi possível processar o download desta mídia.\n\nDetalhe: {$detail}", 422);
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

// ---- Increment Download Counter ----------------------------------------- //
require_once __DIR__ . '/includes/counter.php';
incrementDownloadCount();

// ---- Set Download Token Cookie ------------------------------------------ //

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

$asciiName  = preg_replace('/[^\x20-\x7E]/', '_', $fileName);
$encodedName = rawurlencode($fileName);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($asciiName) . '"; filename*=UTF-8\'\'' . $encodedName);
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$fp = fopen($filePath, 'rb');
while (!feof($fp) && connection_status() === 0) {
    echo fread($fp, 1_048_576);
    flush();
}
fclose($fp);

// ---- Cleanup ------------------------------------------------------------ //

cleanup($tmpDir);
exit;
