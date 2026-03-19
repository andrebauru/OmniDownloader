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

// ---- Input -------------------------------------------------------------- //

$query   = isset($_GET['q'])    ? trim($_GET['q'])    : '';
$page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;

if ($query === '' || mb_strlen($query) > 200) {
    jsonError('Termo de busca inválido.');
}

$page = max(1, $page);

// ---- Session Cache Key -------------------------------------------------- //

$cacheKey = 'search_' . md5(mb_strtolower($query));

// ---- Fetch Results (only when not cached) ------------------------------- //

if (!isset($_SESSION[$cacheKey])) {

    // Fetch up to 30 results so pages 1-3 work without re-querying
    $escapedQuery = escapeshellarg('ytsearch30:' . $query);
    $cmd          = "yt-dlp {$escapedQuery} --flat-playlist --dump-single-json --no-download 2>&1";

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
        $id = $entry['id'];

        $entries[] = [
            'id'        => $id,
            'title'     => $entry['title']       ?? 'Sem título',
            'url'       => $entry['webpage_url'] ?? $entry['url'] ?? "https://www.youtube.com/watch?v={$id}",
            'thumbnail' => $entry['thumbnail']   ?? "https://i.ytimg.com/vi/{$id}/mqdefault.jpg",
            'duration'  => isset($entry['duration']) ? (int) $entry['duration'] : 0,
            'uploader'  => $entry['uploader']    ?? $entry['channel'] ?? '',
            'platform'  => 'YouTube',
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
