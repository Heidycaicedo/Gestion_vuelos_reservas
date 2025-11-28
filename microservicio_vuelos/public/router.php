<?php
// Router para el servidor embebido de PHP.
// Si el archivo solicitado existe físicamente, sirve el archivo.
// En caso contrario, reenvía la petición a index.php (Slim app entry).
if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
