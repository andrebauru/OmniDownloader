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

function findFFmpeg(): ?string
{
    // Tenta encontrar ffmpeg no PATH
    $ffmpeg = shell_exec('which ffmpeg 2>/dev/null') ?? shell_exec('where ffmpeg 2>nul');
    if ($ffmpeg) {
        return trim($ffmpeg);
    }
    
    // Tenta no diretório local do projeto (Windows)
    $localFFmpeg = __DIR__ . DIRECTORY_SEPARATOR . 'ffmpeg_bin' . DIRECTORY_SEPARATOR . 'ffmpeg-8.0.1-essentials_build' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe';
    if (is_file($localFFmpeg)) {
        return $localFFmpeg;
    }
    
    // Tenta versão alternativa do caminho local
    $items = glob(__DIR__ . DIRECTORY_SEPARATOR . 'ffmpeg_bin' . DIRECTORY_SEPARATOR . 'ffmpeg-*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe') ?: [];
    if (!empty($items)) {
        return reset($items);
    }
    
    return null;
}

function getInstagramSessionCookiesPath(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omnidownloader_instagram_session.txt';
}

function getInstagramSessionCookie(): ?array
{
    $sessionFile = getInstagramSessionCookiesPath();
    
    // Verificar se há arquivo de sessão e se não expirou (24 horas)
    if (is_file($sessionFile)) {
        $mtime = filemtime($sessionFile);
        if ($mtime && (time() - $mtime) < 86400) {
            $cookieContent = @file_get_contents($sessionFile);
            if ($cookieContent && strlen($cookieContent) > 50) {
                // Sessão válida encontrada
                @touch($sessionFile); // Atualizar timestamp para estender 24h
                return ['--cookies', $sessionFile];
            }
        }
        @unlink($sessionFile); // Deletar sessão expirada
    }
    
    return null;
}

function saveInstagramSessionCookies(string $outputDir): void
{
    // Procurar por arquivo de cookies gerado pelo yt-dlp no diretório temporário
    // yt-dlp pode gerar cookies em um arquivo ao fazer download
    $sessionFile = getInstagramSessionCookiesPath();
    
    // Verificar se há arquivo cookies.txt no output
    $possiblePaths = [
        $outputDir . DIRECTORY_SEPARATOR . 'cookies.txt',
        sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yt-dlp-cookies.txt',
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_file($path)) {
            $content = @file_get_contents($path);
            if ($content && strlen($content) > 50) {
                @copy($path, $sessionFile);
                @chmod($sessionFile, 0644);
                break;
            }
        }
    }
}

function extractAndSaveInstagramCookies(string $ytdlpOutput): void
{
    // Se yt-dlp extraiu cookies da sessão anterior, salvá-los
    if (strpos($ytdlpOutput, 'Successfully loaded') !== false || 
        strpos($ytdlpOutput, 'Extracting') !== false) {
        // Sucesso indicado, tentar extrair cookies
        $sessionFile = getInstagramSessionCookiesPath();
        
        // Tenta extrair do cookies.txt local
        if (is_file('cookies.txt')) {
            @copy('cookies.txt', $sessionFile);
            @chmod($sessionFile, 0644);
        }
    }
}

function optimizeVideoForChat(string $inputFile): string
{
    $ffmpeg = findFFmpeg();
    if (!$ffmpeg) {
        // FFmpeg não disponível, retorna arquivo original
        return $inputFile;
    }
    
    $outputFile = $inputFile . '.optimized.mp4';
    
    // Re-codificar para H.264 + AAC (máxima compatibilidade com apps de chat)
    // Usar preset fast para não demorar muito
    $cmd = implode(' ', array_map('escapeshellarg', [
        $ffmpeg,
        '-i', $inputFile,
        '-c:v', 'h264',           // Codec de vídeo H.264 (compatível com tudo)
        '-preset', 'fast',        // Velocidade (fast = menos CPU, mais rápido)
        '-crf', '28',             // Qualidade (28 = boa qualidade, arquivo menor)
        '-maxrate', '5000k',      // Limite máximo de bitrate
        '-bufsize', '10000k',     // Buffer para evitar picos
        '-c:a', 'aac',            // Codec de áudio AAC
        '-b:a', '128k',           // Bitrate de áudio
        '-movflags', '+faststart',// Otimizar para streaming (mostra no início)
        '-y',                     // Sobrescrever sem perguntar
        $outputFile,
    ])) . ' 2>&1';
    
    @exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && is_file($outputFile)) {
        // Remover arquivo original e renomear otimizado
        @unlink($inputFile);
        @rename($outputFile, $inputFile);
        return $inputFile;
    }
    
    // Se falhar, retorna arquivo original
    @unlink($outputFile);
    return $inputFile;
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
    // Verifica se podemos acessar cookies de navegadores.
    // Em servidores web, nao podemos.
    
    if (PHP_OS_FAMILY !== 'Windows') {
        $scriptDir = __DIR__;
        
        // Se estamos em /var/www ou /home/.../public_html, eh servidor
        if (strpos($scriptDir, '/var/www') === 0 || 
            (strpos($scriptDir, '/home') === 0 && strpos($scriptDir, 'public_html') !== false)) {
            return false;
        }
        
        // Tambem verificar usuario
        $currentUser = trim(shell_exec('whoami') ?? '');
        if (in_array($currentUser, ['www-data', 'www', 'httpd', 'apache', 'nginx', '_www', 'nobody'], true)) {
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

function waitBetweenAttempts(int $attempt, string $url): void
{
    // Para Instagram, aguardar progressivamente entre tentativas (mas bem curto)
    $isInstagram = stripos($url, 'instagram.com') !== false;
    
    if (!$isInstagram) {
        return; // Sem delay para outros sites
    }
    
    // Delay MUITO curto (milissegundos) - apenas para não martirizar
    // O real rate-limit é entre DOWNLOADS diferentes, não entre tentativas de yt-dlp
    $delay = min($attempt * 0.5, 1); // 0.5s, 1s max
    
    if ($delay > 0) {
        usleep($delay * 1_000_000);
    }
}

function enforceInstagramDownloadThrottle(): void
{
    // IMPORTANTE: Instagram bloqueia múltiplos downloads muito rápidos
    // Este throttle é POR NAVEGADOR, não por servidor
    // Se downloads sucessivos vêm do mesmo IP/browser, será bloqueado
    
    // Verificar último download
    $lastDownloadFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omnidownloader_instagram_last_download.txt';
    
    if (is_file($lastDownloadFile)) {
        $lastTime = (int) file_get_contents($lastDownloadFile);
        $timeSinceLastDownload = time() - $lastTime;
        
        // Se menos de 90 segundos desde último download
        if ($timeSinceLastDownload < 90) {
            $waitTime = 90 - $timeSinceLastDownload;
            // NÃO bloquear aqui, apenas avisar no frontend
            // O usuário pode fazer outro download, mas Instagram pode bloquear
        }
    }
}function getRandomUserAgent(): string
{
    $agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    ];
    return $agents[array_rand($agents)];
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
    '--socket-timeout', '30',
    '--retries', '5',
    '--fragment-retries', '5',
    '--extractor-retries', '3',
    '--user-agent', getRandomUserAgent(),
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
    // Priorizar H.264 + AAC para máxima compatibilidade com apps de chat
    // Fallback para qualquer formato se necessário
    $baseArgs[] = '-f';
    $baseArgs[] = 'bestvideo[vcodec^=h264][ext=mp4]+bestaudio[acodec=aac]/bestvideo[vcodec^=h264]+bestaudio/bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best';
    $baseArgs[] = '--merge-output-format';
    $baseArgs[] = 'mp4';
}

$baseArgs[] = $url;

// ---- Execute yt-dlp ----------------------------------------------------- //

$isYoutube = isYoutubeUrl($url);
$isInstagram = stripos($url, 'instagram.com') !== false;
$isTwitter = stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false;

// Para Instagram, verificar throttle
if ($isInstagram) {
    enforceInstagramDownloadThrottle();
}

$attempts = [$baseArgs];

if ($isYoutube || $isInstagram || $isTwitter) {
    // Para Instagram, tentar usar sessão persistente primeiro (cookies salvos)
    if ($isInstagram) {
        $sessionCookie = getInstagramSessionCookie();
        if ($sessionCookie) {
            $attempts[] = array_merge($baseArgs, $sessionCookie);
        }
    }
    
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

foreach ($attempts as $attemptIndex => $attemptArgs) {
    // Aguardar entre tentativas (especialmente importante para Instagram)
    if ($attemptIndex > 0) {
        waitBetweenAttempts($attemptIndex, $url);
    }
    
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

    // Instagram rate-limit detection - more comprehensive
    if ($isInstagram && (str_contains($normalizedDetail, 'rate') || 
                         str_contains($normalizedDetail, 'too many') ||
                         str_contains($normalizedDetail, 'please wait') ||
                         str_contains($normalizedDetail, 'temporarily blocked') ||
                         str_contains($normalizedDetail, 'retry after') ||
                         str_contains($normalizedDetail, 'request was denied') ||
                         str_contains($normalizedDetail, '429') ||
                         str_contains($normalizedDetail, 'throttled'))) {
        sendError(
            "Instagram bloqueou temporariamente por excesso de requisicoes.\n\n"
            . "Aguarde 5-10 minutos antes de tentar novamente.\n\n"
            . "Dica: Se estiver tentando varias downloads em sequencia,\n"
            . "aguarde alguns minutos entre cada download para evitar bloqueio.\n\n"
            . "Detalhe: {$detail}",
            429
        );
    }
    
    // Instagram authentication required
    if ($isInstagram && (str_contains($normalizedDetail, 'login required') || 
                         str_contains($normalizedDetail, 'not available') ||
                         str_contains($normalizedDetail, 'private account'))) {
        sendError(
            "Instagram requer autenticacao para baixar este conteudo.\n\n"
            . "Solucoes:\n"
            . "1. Certifique-se de que o video eh publico\n"
            . "2. Configure um arquivo cookies.txt com autenticacao do Instagram\n"
            . "3. Veja INSTAGRAM_COOKIES.md para mais detalhes\n\n"
            . "Detalhe: {$detail}",
            403
        );
    }

    if (str_contains($normalizedDetail, "sign in to confirm you're not a bot")) {
        $detectedBrowsers = detectInstalledBrowsers();
        $browserList = !empty($detectedBrowsers) 
            ? implode(', ', array_map('ucfirst', $detectedBrowsers))
            : 'Chrome/Edge/Firefox/Brave';
        
        sendError(
            "O YouTube esta exigindo verificacao anti-bot para este video.\n"
            . "O servidor ja tentou ativar cookies automaticamente ({$browserList}), mas nao conseguiu autenticar.\n\n"
            . "Detalhe tecnico: {$detail}",
            429
        );
    }

    if ($detail === '') {
        $detail = 'Erro desconhecido ao processar o video. Verifique se a URL eh valida.';
    }

    sendError("Nao foi possivel processar o download desta midia.\n\nDetalhe: {$detail}", 422);
}

// ---- Locate Output File ------------------------------------------------- //

$files = array_filter(glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [], 'is_file');

if (empty($files)) {
    cleanup($tmpDir);
    sendError('Arquivo não encontrado após o download. Tente novamente.', 500);
}

$filePath = (string) reset($files);

// ---- Optimize Video for Chat Compatibility ----------------------------- //

if ($format === 'video') {
    // Reconverter vídeo para máxima compatibilidade com apps de chat
    // (H.264 + AAC é compatível com WhatsApp, Telegram, Instagram, etc)
    $filePath = optimizeVideoForChat($filePath);
}

$fileName = basename($filePath);
$fileSize = filesize($filePath);
$mimeType = ($format === 'mp3') ? 'audio/mpeg' : 'video/mp4';

// ---- Save Instagram Session for Next Download ----------------------- //
if ($isInstagram) {
    saveInstagramSessionCookies($tmpDir);
    // Registrar timestamp do último download bem-sucedido
    @file_put_contents(
        sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omnidownloader_instagram_last_download.txt',
        time()
    );
}

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
