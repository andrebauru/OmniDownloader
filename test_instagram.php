<?php
/**
 * Test script to debug Instagram downloads
 */

header('Content-Type: text/plain; charset=utf-8');

$url = 'https://www.instagram.com/reel/DUG-U3bkcmb/';

echo "=== Testing Instagram Download ===\n\n";
echo "URL: $url\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OS Family: " . PHP_OS_FAMILY . "\n";
echo "Current User: " . trim(shell_exec('whoami') ?? 'unknown') . "\n";
echo "Current Dir: " . __DIR__ . "\n\n";

// Test 1: Check yt-dlp
echo "--- Test 1: yt-dlp availability ---\n";
exec('yt-dlp --version 2>&1', $output, $code);
echo "Return Code: $code\n";
echo "Output: " . implode("\n", $output) . "\n\n";

// Test 2: Try basic Instagram download (info only)
echo "--- Test 2: Get Instagram video info ---\n";
$cmd = "yt-dlp --no-warnings --dump-single-json --no-download " . escapeshellarg($url) . " 2>&1";
echo "Command: $cmd\n";
exec($cmd, $output, $code);
echo "Return Code: $code\n";
echo "Output Lines: " . count($output) . "\n";
echo "First 500 chars:\n";
echo substr(implode("\n", $output), 0, 500) . "\n\n";

if ($code === 0) {
    echo "✓ Successfully got Instagram video info!\n";
} else {
    echo "✗ Failed to get Instagram video info.\n";
    echo "Full output:\n";
    echo implode("\n", $output) . "\n";
}
