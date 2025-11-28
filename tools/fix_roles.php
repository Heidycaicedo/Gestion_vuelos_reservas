<?php
require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configure Eloquent ORM
$capsule = new Capsule;
$config = require __DIR__ . '/../microservicio_usuarios/config/database.php';
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Query
$users = Capsule::table('users')->whereIn('email', ['admin@system.com', 'gestor@system.com'])->get();

echo "=== USERS IN DATABASE ===\n";
foreach ($users as $user) {
    echo "ID: {$user->id}, Email: {$user->email}, Role: {$user->role}\n";
}

// Fix if needed
echo "\n=== FIXING ROLES ===\n";
Capsule::table('users')->where('email', 'admin@system.com')->update(['role' => 'administrador']);
echo "✓ Updated admin@system.com to role='administrador'\n";

Capsule::table('users')->where('email', 'gestor@system.com')->update(['role' => 'gestor']);
echo "✓ Updated gestor@system.com to role='gestor'\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$users = Capsule::table('users')->whereIn('email', ['admin@system.com', 'gestor@system.com'])->get();
foreach ($users as $user) {
    echo "ID: {$user->id}, Email: {$user->email}, Role: {$user->role}\n";
}
?>
