<?php
/**
 * OmniDownloader — Search API
 *
 * Searches YouTube via yt-dlp (ytsearch) and returns paginated JSON results.
 * Results are cached in the PHP session to avoid re-fetching on page navigation.
 *
 * Usage: GET /search.php?q=<query>&page=<n>
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---- Helpers ------------------------------------------------------------ //

function jsonError(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['error' => $msg, 'results' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

function isValidHttpUrl(string $value): bool
{
    return (bool) filter_var($value, FILTER_VALIDATE_URL)
        && (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0);
}

function normalizeTiktokUser(string $query): string
{
    $q = trim($query);
    $q = preg_replace('~^https?://(?:www\.)?tiktok\.com/@~i', '', $q);
    $q = preg_replace('~[/?#].*$~', '', $q);
    $q = ltrim((string) $q, '@');
    $q = preg_replace('/[^a-zA-Z0-9._]/', '', (string) $q);
    return (string) $q;
}

function normalizeResultUrl(array $entry, string $platform): ?string
{
    $id  = (string) ($entry['id'] ?? '');
    $url = (string) ($entry['webpage_url'] ?? $entry['url'] ?? '');

    if (isValidHttpUrl($url)) {
        return $url;
    }

    if ($platform === 'youtube' && $id !== '') {
        return 'https://www.youtube.com/watch?v=' . rawurlencode($id);
    }

    if ($platform === 'tiktok') {
        $uploader = (string) ($entry['uploader'] ?? $entry['channel'] ?? '');
        $uploader = ltrim($uploader, '@');
        if ($uploader !== '' && $id !== '') {
            return 'https://www.tiktok.com/@' . rawurlencode($uploader) . '/video/' . rawurlencode($id);
        }
    }

    if ($platform === 'soundcloud') {
        $uploader = (string) ($entry['uploader'] ?? $entry['channel'] ?? '');
        if ($uploader !== '' && $id !== '' && strpos($id, 'http') !== 0) {
            return 'https://soundcloud.com/' . rawurlencode($uploader) . '/' . rawurlencode($id);
        }
    }

    return null;
}

// ---- Input -------------------------------------------------------------- //

$query    = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$page     = isset($_GET['page'])    ? (int) $_GET['page']    : 1;
$platform = isset($_GET['platform']) ? trim($_GET['platform']) : 'youtube';
$perPage  = 10;

if ($query === '' || mb_strlen($query) > 200) {
    jsonError('Termo de busca inválido.');
}

$page     = max(1, $page);
$platform = in_array($platform, ['youtube', 'soundcloud', 'tiktok'], true) ? $platform : 'youtube';

if ($platform === 'soundcloud') {
    $searchTarget = 'scsearch30:' . $query;
} elseif ($platform === 'tiktok') {
    $user = normalizeTiktokUser($query);
    if ($user === '') {
        jsonError('Para TikTok, digite um usuário válido (ex.: @charlidamelio).', 422);
    }
    $searchTarget = 'tiktokuser:' . $user;
} else {
    $searchTarget = 'ytsearch30:' . $query;
}

// ---- Session Cache Key -------------------------------------------------- //

$cacheKey = 'search_' . md5(mb_strtolower($query) . '_' . $platform);

// ---- Fetch Results (only when not cached) ------------------------------- //

if (!isset($_SESSION[$cacheKey])) {

    // Fetch up to 30 results so pages 1-3 work without re-querying
    $escapedQuery = escapeshellarg($searchTarget);
    $cmd          = "yt-dlp {$escapedQuery} --flat-playlist --playlist-end 30 --dump-single-json --no-download 2>&1";

    exec($cmd, $outputLines, $returnCode);

    if ($returnCode !== 0) {
        jsonError('Erro ao buscar. Verifique se yt-dlp está instalado no servidor.', 500);
    }

    // yt-dlp may prefix output with log/warning lines; find the JSON object
    $jsonLine = '';
    foreach ($outputLines as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '{')) {
            $jsonLine = $trimmed;
            break;
        }
    }

    if ($jsonLine === '') {
        jsonError('Nenhum resultado encontrado para "' . htmlspecialchars($query) . '".', 404);
    }

    $data = json_decode($jsonLine, true);

    if (!is_array($data) || empty($data['entries'])) {
        jsonError('Nenhum resultado encontrado para "' . htmlspecialchars($query) . '".', 404);
    }

    // Normalize entries
    $entries = [];
    foreach ($data['entries'] as $entry) {
        if (empty($entry['id'])) {
            continue;
        }
        $id  = (string) $entry['id'];
        $url = normalizeResultUrl($entry, $platform);
        if ($url === null) {
            continue;
        }

        $entries[] = [
            'id'        => $id,
            'title'     => $entry['title']       ?? 'Sem título',
            'url'       => $url,
            'thumbnail' => $entry['thumbnail']   ?? ($platform === 'youtube' ? "https://i.ytimg.com/vi/{$id}/mqdefault.jpg" : ''),
            'duration'  => isset($entry['duration']) ? (int) $entry['duration'] : 0,
            'uploader'  => $entry['uploader']    ?? $entry['channel'] ?? '',
            'platform'  => $platform === 'soundcloud' ? 'SoundCloud' : ($platform === 'tiktok' ? 'TikTok' : 'YouTube'),
        ];
    }

    if (empty($entries)) {
        jsonError('Nenhum resultado encontrado.', 404);
    }

    // Cache in session, purge other old search caches to save memory
    foreach (array_keys($_SESSION) as $key) {
        if (str_starts_with($key, 'search_') && $key !== $cacheKey) {
            unset($_SESSION[$key]);
        }
    }
    $_SESSION[$cacheKey] = $entries;
}

// ---- Paginate ----------------------------------------------------------- //

$allResults = $_SESSION[$cacheKey];
$total      = count($allResults);
$totalPages = (int) ceil($total / $perPage);
$page       = min($page, max(1, $totalPages));
$offset     = ($page - 1) * $perPage;
$results    = array_values(array_slice($allResults, $offset, $perPage));

// ---- Respond ------------------------------------------------------------ //

echo json_encode([
    'results'    => $results,
    'total'      => $total,
    'page'       => $page,
    'totalPages' => $totalPages,
    'perPage'    => $perPage,
    'query'      => $query,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
