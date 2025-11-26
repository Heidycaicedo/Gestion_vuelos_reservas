<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Aircraft;

class AircraftController
{
    // 3.2 Consultar naves disponibles
    public function list(Request $request, Response $response)
    {
        try {
            $aircraft = Aircraft::all();
            $response->getBody()->write(json_encode(['success' => true, 'data' => $aircraft]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 3.2 Consultar nave específica
    public function show(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Nave no encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $aircraft]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 3.1 Registrar nueva nave
    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            // Validar datos requeridos
            if (empty($data['modelo']) || empty($data['capacidad']) || empty($data['matricula'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'modelo, capacidad y matricula son requeridos'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validar que la capacidad sea un número válido
            if (!is_numeric($data['capacidad']) || $data['capacidad'] <= 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'La capacidad debe ser un número positivo'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validar que la matrícula no esté duplicada
            $existingAircraft = Aircraft::where('matricula', $data['matricula'])->first();
            if ($existingAircraft) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Ya existe una nave con esa matrícula'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $aircraft = Aircraft::create([
                'modelo' => $data['modelo'],
                'capacidad' => $data['capacidad'],
                'matricula' => $data['matricula']
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $aircraft]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 3.3 Modificar información de una nave
    public function update(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Nave no encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $data = json_decode($request->getBody(), true);

            // Validar que la capacidad sea válida si se actualiza
            if (isset($data['capacidad'])) {
                if (!is_numeric($data['capacidad']) || $data['capacidad'] <= 0) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'La capacidad debe ser un número positivo'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Validar que la matrícula sea única si se actualiza
            if (isset($data['matricula']) && $data['matricula'] !== $aircraft->matricula) {
                $existingAircraft = Aircraft::where('matricula', $data['matricula'])->first();
                if ($existingAircraft) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Ya existe una nave con esa matrícula'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
                }
            }

            $aircraft->update($data);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $aircraft]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 3.4 Eliminar una nave
    public function delete(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Nave no encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Validar que no haya vuelos asociados a esta nave
            $flightCount = \Illuminate\Database\Capsule\Manager::table('vuelos')
                ->where('nave_id', $aircraft->id)
                ->count();

            if ($flightCount > 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No se puede eliminar una nave que tiene vuelos asociados. Elimina primero los vuelos.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $aircraft->delete();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Nave eliminada correctamente'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
