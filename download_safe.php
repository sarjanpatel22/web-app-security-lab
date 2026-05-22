<?php
// FIX: Directory Traversal -> strip path components and confirm the resolved
// real path stays inside the allowed base directory.
require_once __DIR__ . '/config.php';

$file = basename($_GET['file'] ?? '');   // drop any ../ path parts
$base = realpath(FILES_DIR);
$path = realpath($base . '/' . $file);

if ($path === false || strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

header('Content-Type: text/plain');
readfile($path);
