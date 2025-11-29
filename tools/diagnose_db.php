<?php

require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    echo "[1/3] Conectando a la base de datos...\n";
    $config = require __DIR__ . '/../microservicio_usuarios/config/database.php';

    $capsule = new Capsule;
    $capsule->addConnection($config);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    echo "✓ Conexión exitosa\n\n";

    echo "[2/3] Verificando tabla users...\n";
    $users = Capsule::table('users')->limit(3)->get();
    echo "Total de usuarios: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "  - ID {$u->id}: {$u->email} (role: {$u->role})\n";
    }
    echo "\n";

    echo "[3/3] Buscando admin@system.com...\n";
    $admin = Capsule::table('users')->where('email', 'admin@system.com')->first();
    if ($admin) {
        echo "✓ Usuario encontrado\n";
        echo "  ID: {$admin->id}\n";
        echo "  Email: {$admin->email}\n";
        echo "  Role: {$admin->role}\n";
        echo "  Password hash: " . substr($admin->password, 0, 30) . "...\n";
        echo "  Token: " . (empty($admin->token) ? 'NULL/vacío' : substr($admin->token, 0, 20) . "...") . "\n";

        // Try password_verify
        echo "\n  Verificando password 'admin123'...\n";
        if (password_verify('admin123', $admin->password)) {
            echo "  ✓ Password coincide\n";
        } else {
            echo "  ✗ Password NO coincide\n";
            // Try other common passwords
            $passwords = ['123456', 'password', 'admin', 'Admin123!'];
            echo "  Intentando otras contraseñas...\n";
            foreach ($passwords as $pwd) {
                if (password_verify($pwd, $admin->password)) {
                    echo "    ✓ Contraseña correcta es: '$pwd'\n";
                    break;
                }
            }
        }
    } else {
        echo "✗ Usuario NO encontrado\n";
        echo "  Creando usuario admin@system.com con password 'admin123'...\n";
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        Capsule::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@system.com',
            'password' => $hash,
            'role' => 'administrador',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        echo "  ✓ Usuario creado\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

?>