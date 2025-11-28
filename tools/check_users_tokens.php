<?php

$pdo = new PDO('mysql:host=localhost;dbname=vuelos_app', 'root', '');
$stmt = $pdo->query('SELECT id, name, email, role, token FROM users');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Users in database:\n";
foreach ($results as $user) {
    echo "ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}, Token: " . (empty($user['token']) ? '(empty)' : $user['token']) . "\n";
}
?>
