<?php
require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$config = require __DIR__ . '/../microservicio_usuarios/config/database.php';

$capsule = new Capsule;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$emails = ['admin@test.local','gestor@test.local','publicuser@test.local'];
foreach ($emails as $e) {
    $user = Capsule::table('users')->where('email', $e)->first();
    if ($user) {
        Capsule::table('users')->where('email', $e)->update(['role' => 'inactivo']);
        echo "Marked $e as inactivo\n";
    } else {
        echo "User $e not found\n";
    }
}

echo "Done.\n";
