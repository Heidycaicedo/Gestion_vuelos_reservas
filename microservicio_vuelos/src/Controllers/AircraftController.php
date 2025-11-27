<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Aircraft;

class AircraftController
{
    private function successResponse(Response $response, $data, $status = 200)
    {
        $response->getBody()->write(json_encode(['success' => true, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function errorResponse(Response $response, $error, $status = 400)
    {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $error]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function list(Request $request, Response $response)
    {
        try {
            $aircraft = Aircraft::all();
            return $this->successResponse($response, $aircraft);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                return $this->errorResponse($response, 'Nave no encontrada', 404);
            }

            return $this->successResponse($response, $aircraft);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            if (empty($data['name']) || empty($data['capacity']) || empty($data['model'])) {
                return $this->errorResponse($response, 'name, capacity y model son requeridos', 400);
            }

            if (!is_numeric($data['capacity']) || $data['capacity'] <= 0) {
                return $this->errorResponse($response, 'La capacidad debe ser un número positivo', 400);
            }

            $aircraft = Aircraft::create([
                'name' => $data['name'],
                'capacity' => $data['capacity'],
                'model' => $data['model']
            ]);

            return $this->successResponse($response, $aircraft, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                return $this->errorResponse($response, 'Nave no encontrada', 404);
            }

            $data = json_decode($request->getBody(), true);

            if (isset($data['capacity'])) {
                if (!is_numeric($data['capacity']) || $data['capacity'] <= 0) {
                    return $this->errorResponse($response, 'La capacidad debe ser un número positivo', 400);
                }
            }

            $aircraft->update($data);

            return $this->successResponse($response, $aircraft);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $aircraft = Aircraft::find($args['id']);

            if (!$aircraft) {
                return $this->errorResponse($response, 'Nave no encontrada', 404);
            }

            $flightCount = \Illuminate\Database\Capsule\Manager::table('flights')
                ->where('nave_id', $aircraft->id)
                ->count();

            if ($flightCount > 0) {
                return $this->errorResponse($response, 'No se puede eliminar una nave que tiene vuelos asociados. Elimina primero los vuelos.', 409);
            }

            $aircraft->delete();

            return $this->successResponse($response, ['message' => 'Nave eliminada correctamente']);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }
}
