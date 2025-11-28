<?php
require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

function testConnection($configPath, $label) {
    try {
        $config = require $configPath;
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection($config);
        $pdo = $capsule->getConnection()->getPdo();
        echo "$label: OK\n";
    } catch (Throwable $e) {
        echo "$label: ERROR - " . $e->getMessage() . "\n";
    }
}

$base = __DIR__ . '/../';
$uConfig = $base . 'microservicio_usuarios/config/database.php';
$vConfig = $base . 'microservicio_vuelos/config/database.php';

// Autoload both vendors (they are similar, but require once was done above)
if (file_exists($base . 'microservicio_vuelos/vendor/autoload.php')) {
    require_once $base . 'microservicio_vuelos/vendor/autoload.php';
}

testConnection($uConfig, 'USUARIOS_DB');
testConnection($vConfig, 'VUELOS_DB');
