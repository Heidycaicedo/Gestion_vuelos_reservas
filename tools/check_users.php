<?php
require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$config = require __DIR__ . '/../microservicio_usuarios/config/database.php';

$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Obtener usuarios
$users = \Illuminate\Database\Capsule\Manager::table('users')
    ->select('id', 'name', 'email', 'role')
    ->limit(5)
    ->get();

echo "=== Usuarios en BD ===\n";
foreach ($users as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Role: {$user->role}\n";
}

// Obtener vuelos
$flights = \Illuminate\Database\Capsule\Manager::table('flights')
    ->select('id', 'origin', 'destination', 'departure')
    ->limit(5)
    ->get();

echo "\n=== Vuelos en BD ===\n";
foreach ($flights as $flight) {
    echo "ID: {$flight->id} | {$flight->origin} → {$flight->destination} | {$flight->departure}\n";
}
