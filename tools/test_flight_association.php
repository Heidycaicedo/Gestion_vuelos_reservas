<?php
// Verificar que los vuelos están asociados a naves
require_once __DIR__ . '/../microservicio_vuelos/config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

// Consultar vuelos con información de naves
$flights = DB::table('flights')
    ->leftJoin('naves', 'flights.nave_id', '=', 'naves.id')
    ->select('flights.id', 'flights.nave_id', 'naves.name', 'naves.model', 'flights.origin', 'flights.destination', 'flights.departure', 'flights.price')
    ->get();

echo "=== VERIFICACIÓN DE VUELOS Y NAVES ASOCIADAS ===\n\n";

if ($flights->isEmpty()) {
    echo "No hay vuelos registrados.\n";
    exit;
}

foreach ($flights as $flight) {
    echo "Vuelo #" . $flight->id . "\n";
    echo "  Nave ID: " . $flight->nave_id . "\n";
    if ($flight->name) {
        echo "  Nave: " . $flight->name . " (" . $flight->model . ")\n";
    } else {
        echo "  Nave: NO DISPONIBLE\n";
    }
    echo "  Ruta: " . $flight->origin . " → " . $flight->destination . "\n";
    echo "  Salida: " . $flight->departure . "\n";
    echo "  Precio: $" . $flight->price . "\n\n";
}

echo "Total de vuelos: " . count($flights) . "\n";
