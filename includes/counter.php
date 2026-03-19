<?php
/**
 * Download counter — file-based with exclusive locking for safe concurrent writes.
 * Stored in data/downloads.count as a plain integer.
 */

define('COUNTER_FILE', __DIR__ . '/../data/downloads.count');

function getDownloadCount(): int
{
    if (!file_exists(COUNTER_FILE)) {
        return 0;
    }
    $val = @file_get_contents(COUNTER_FILE);
    return $val !== false ? (int) $val : 0;
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

    $count = (int) fread($fp, 32);
    $count++;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) $count);
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    return $count;
}
