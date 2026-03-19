<?php
/**
 * Download counter — file-based with exclusive locking for safe concurrent writes.
 * Stored in data/downloads.json as { "total": N, "updated_at": "ISO8601" }.
 */

define('COUNTER_FILE', __DIR__ . '/../data/downloads.json');

function getDownloadCount(): int
{
    if (!file_exists(COUNTER_FILE)) {
        return 0;
    }
    $json = @file_get_contents(COUNTER_FILE);
    if ($json === false) {
        return 0;
    }
    $data = json_decode($json, true);
    return isset($data['total']) ? (int) $data['total'] : 0;
}

function incrementDownloadCount(): int
{
    $dir = dirname(COUNTER_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fp = @fopen(COUNTER_FILE, 'c+'); // create if not exists, no truncate
    if (!$fp) {
        return 0;
    }

    flock($fp, LOCK_EX);

    $raw  = stream_get_contents($fp);
    $data = $raw !== '' ? json_decode($raw, true) : [];
    $count = isset($data['total']) ? (int) $data['total'] + 1 : 1;

    $payload = json_encode([
        'total'      => $count,
        'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ], JSON_PRETTY_PRINT);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $payload);
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    return $count;
}
