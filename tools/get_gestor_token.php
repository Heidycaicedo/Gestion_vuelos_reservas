<?php

$pdo = new PDO('mysql:host=localhost;dbname=vuelos_app', 'root', '');
$stmt = $pdo->query('SELECT token FROM users WHERE role = "gestor" LIMIT 1');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    echo 'Gestor token: ' . $result['token'] . PHP_EOL;
} else {
    echo 'No gestor token found' . PHP_EOL;
}
?>
