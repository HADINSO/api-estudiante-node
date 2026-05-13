<?php

// ══════════════════════════════════════════════════════════════
//  router.php — Para usar con el servidor built-in de PHP:
//  php -S localhost:3000 router.php
//
//  Redirige todas las peticiones a index.php preservando
//  la URI original (equivalente al .htaccess de Apache)
// ══════════════════════════════════════════════════════════════

$uri = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

// Servir archivos estáticos si existen (ej: el propio JSON)
if ($uri !== "/" && file_exists(__DIR__ . $uri)) {
    return false;
}

// Todo lo demás va a index.php
require __DIR__ . "/index.php";