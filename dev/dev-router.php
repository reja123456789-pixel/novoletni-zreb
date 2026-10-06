<?php
// Lokalni strežnik (start.bat): PHP datoteke izvede kot običajno,
// ostale datoteke pošlje brez predpomnjenja, da brskalnik vedno pokaže najnovejšo različico.

$root = realpath(__DIR__ . '/../htdocs');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_ends_with($uri, '.php')) return false;

$file = realpath($root . $uri);
if ($file !== false && is_dir($file)) $file = realpath($file . '/index.html');
if ($file === false || strpos($file, $root) !== 0 || !is_file($file)) return false;

$types = [
    'html' => 'text/html; charset=utf-8',
    'js'   => 'text/javascript; charset=utf-8',
    'css'  => 'text/css; charset=utf-8',
    'mp3'  => 'audio/mpeg',
    'png'  => 'image/png',
    'svg'  => 'image/svg+xml',
];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-store, must-revalidate');
readfile($file);
return true;
