<?php
/**
 * OmniDownloader — Stats API
 * Returns current download count as JSON.
 * GET /stats.php
 */

require_once __DIR__ . '/includes/counter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

echo json_encode(['count' => getDownloadCount()]);
