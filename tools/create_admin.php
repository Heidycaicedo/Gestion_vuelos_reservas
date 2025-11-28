<?php

require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$config = require __DIR__ . '/../microservicio_usuarios/config/database.php';

$capsule = new Capsule;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$email = 'admin@test.local';
$plain = 'adminpass';
$hash = password_hash($plain, PASSWORD_BCRYPT);

// Check existing
$exists = Capsule::table('users')->where('email', $email)->first();
if ($exists) {
    echo "Admin user already exists with id: {$exists->id}\n";
    exit(0);
}

$id = Capsule::table('users')->insertGetId([
    'name' => 'Admin Test',
    'email' => $email,
    'password' => $hash,
    'role' => 'administrador',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

echo "Created admin user id: $id (email: $email, password: $plain)\n";
