<?php

require __DIR__ . '/../microservicio_usuarios/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$config = require __DIR__ . '/../microservicio_usuarios/config/database.php';

$capsule = new Capsule;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$email = $argv[1] ?? 'admin@system.com';
$new = $argv[2] ?? 'admin123';

$hash = password_hash($new, PASSWORD_BCRYPT);

$updated = Capsule::table('users')->where('email', $email)->update([
    'password' => $hash,
    'updated_at' => date('Y-m-d H:i:s')
]);

if ($updated) {
    echo "Updated password for $email to: $new\n";
} else {
    echo "No user updated for $email\n";
}

?>
