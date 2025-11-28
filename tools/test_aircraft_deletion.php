<?php

$pdo = new PDO('mysql:host=localhost;dbname=vuelos_app', 'root', '');

// Check flights for aircraft
$stmt = $pdo->query('SELECT COUNT(*) as count FROM flights WHERE nave_id = 1');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Aircraft 1 has " . $result['count'] . " flights associated" . PHP_EOL;

// Check all aircraft
$stmt = $pdo->query('SELECT id, name, (SELECT COUNT(*) FROM flights WHERE nave_id = naves.id) as flight_count FROM naves ORDER BY id');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nAll Aircraft Status:\n";
foreach ($results as $aircraft) {
    echo "Aircraft ID: {$aircraft['id']}, Name: {$aircraft['name']}, Flights: {$aircraft['flight_count']}" . PHP_EOL;
}
?>
