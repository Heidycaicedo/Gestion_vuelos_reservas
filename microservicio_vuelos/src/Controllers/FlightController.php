<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Flight;

class FlightController
{
    public function list(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            $query = Flight::query();

            // 2.3 Búsqueda por origen, destino o fecha
            if (!empty($params['origen'])) {
                $query->where('origen', 'LIKE', '%' . $params['origen'] . '%');
            }

            if (!empty($params['destino'])) {
                $query->where('destino', 'LIKE', '%' . $params['destino'] . '%');
            }

            if (!empty($params['fecha'])) {
                $query->whereDate('fecha_salida', $params['fecha']);
            }

            if (!empty($params['fecha_desde'])) {
                $query->whereDate('fecha_salida', '>=', $params['fecha_desde']);
            }

            if (!empty($params['fecha_hasta'])) {
                $query->whereDate('fecha_salida', '<=', $params['fecha_hasta']);
            }

            $flights = $query->get();

            $response->getBody()->write(json_encode(['success' => true, 'data' => $flights]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function show(Request $request, Response $response, $args)
    {
        try {
            $flight = Flight::find($args['id']);

            if (!$flight) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Vuelo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $flight]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            // 2.1 Validar datos requeridos para registrar vuelo
            if (empty($data['numero_vuelo']) || empty($data['nave_id']) || empty($data['origen']) || 
                empty($data['destino']) || empty($data['fecha_salida']) || empty($data['fecha_llegada'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'numero_vuelo, nave_id, origen, destino, fecha_salida y fecha_llegada son requeridos'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 2.1 Validar que origen y destino sean diferentes
            if (trim($data['origen']) === trim($data['destino'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'El origen y destino no pueden ser iguales'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 2.1 Validar que fecha_llegada sea posterior a fecha_salida
            if (strtotime($data['fecha_llegada']) <= strtotime($data['fecha_salida'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'La fecha de llegada debe ser posterior a la fecha de salida'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 2.1 Validar que la nave existe
            $aircraft = \Illuminate\Database\Capsule\Manager::table('naves')
                ->where('id', $data['nave_id'])
                ->first();

            if (!$aircraft) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'La nave especificada no existe'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 2.1 Validar que el número de vuelo sea único
            $existingFlight = Flight::where('numero_vuelo', $data['numero_vuelo'])->first();
            if ($existingFlight) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Ya existe un vuelo con ese número'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // 2.1 Establecer asientos disponibles basado en la capacidad de la nave
            $data['asientos_disponibles'] = $aircraft->capacidad;

            $flight = Flight::create($data);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $flight]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $flight = Flight::find($args['id']);

            if (!$flight) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Vuelo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 2.4 Validar campos específicos si se actualizan
            if (isset($data['numero_vuelo']) && $data['numero_vuelo'] !== $flight->numero_vuelo) {
                $existingFlight = Flight::where('numero_vuelo', $data['numero_vuelo'])->first();
                if ($existingFlight) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Ya existe otro vuelo con ese número'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
                }
            }

            // 2.4 Validar que origen y destino sean diferentes
            if (isset($data['origen']) || isset($data['destino'])) {
                $origen = $data['origen'] ?? $flight->origen;
                $destino = $data['destino'] ?? $flight->destino;
                if (trim($origen) === trim($destino)) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'El origen y destino no pueden ser iguales'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // 2.4 Validar que fecha_llegada sea posterior a fecha_salida
            if (isset($data['fecha_salida']) || isset($data['fecha_llegada'])) {
                $fecha_salida = $data['fecha_salida'] ?? $flight->fecha_salida;
                $fecha_llegada = $data['fecha_llegada'] ?? $flight->fecha_llegada;
                if (strtotime($fecha_llegada) <= strtotime($fecha_salida)) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'La fecha de llegada debe ser posterior a la fecha de salida'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // 2.4 Si se cambia la nave, validar que existe
            if (isset($data['nave_id']) && $data['nave_id'] !== $flight->nave_id) {
                $aircraft = \Illuminate\Database\Capsule\Manager::table('naves')
                    ->where('id', $data['nave_id'])
                    ->first();
                if (!$aircraft) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'La nave especificada no existe'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
                }
                // Si hay cambio de nave, actualizar asientos disponibles
                $data['asientos_disponibles'] = $aircraft->capacidad;
            }

            $flight->update($data);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $flight]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $flight = Flight::find($args['id']);

            if (!$flight) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Vuelo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 2.5 Validar que no haya reservas confirmadas para este vuelo
            $reservations = \Illuminate\Database\Capsule\Manager::table('reservas')
                ->where('vuelo_id', $flight->id)
                ->where('estado', 'confirmada')
                ->count();

            if ($reservations > 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $flight->delete();

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Vuelo eliminado']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
