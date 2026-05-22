<?php
require_once __DIR__ . '/config.php';
$file = $_GET['file'] ?? '';
$path = FILES_DIR . '/' . $file;   // FLAW: no path validation
if (is_file($path)) {
    header('Content-Type: text/plain');
    readfile($path);
} else {
    http_response_code(404);
    echo "File not found: " . $file;
}
