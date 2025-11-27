<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Flight;

class FlightController
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
            $params = $request->getQueryParams();
            $query = Flight::query();

            if (!empty($params['origin'])) {
                $query->where('origin', 'LIKE', '%' . $params['origin'] . '%');
            }

            if (!empty($params['destination'])) {
                $query->where('destination', 'LIKE', '%' . $params['destination'] . '%');
            }

            if (!empty($params['departure'])) {
                $query->whereDate('departure', $params['departure']);
            }

            $flights = $query->get();

            return $this->successResponse($response, $flights);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al listar vuelos', 500);
        }
    }

    public function show(Request $request, Response $response, $args)
    {
        try {
            $flight = Flight::find($args['id']);

            if (!$flight) {
                return $this->errorResponse($response, 'Vuelo no encontrado', 404);
            }

            return $this->successResponse($response, $flight);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al consultar vuelo', 500);
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            if (empty($data['nave_id']) || empty($data['origin']) || 
                empty($data['destination']) || empty($data['departure']) || 
                empty($data['arrival']) || empty($data['price'])) {
                return $this->errorResponse($response, 'nave_id, origin, destination, departure, arrival y price son requeridos', 400);
            }

            if (trim($data['origin']) === trim($data['destination'])) {
                return $this->errorResponse($response, 'El origen y destino no pueden ser iguales', 400);
            }

            if (strtotime($data['arrival']) <= strtotime($data['departure'])) {
                return $this->errorResponse($response, 'La fecha de llegada debe ser posterior a la fecha de salida', 400);
            }

            $aircraft = \Illuminate\Database\Capsule\Manager::table('naves')
                ->where('id', $data['nave_id'])
                ->first();

            if (!$aircraft) {
                return $this->errorResponse($response, 'La nave especificada no existe', 404);
            }

            $flight = Flight::create($data);

            return $this->successResponse($response, $flight, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $flight = Flight::find($args['id']);

            if (!$flight) {
                return $this->errorResponse($response, 'Vuelo no encontrado', 404);
            }

            if (isset($data['origin']) || isset($data['destination'])) {
                $origin = $data['origin'] ?? $flight->origin;
                $destination = $data['destination'] ?? $flight->destination;
                if (trim($origin) === trim($destination)) {
                    return $this->errorResponse($response, 'El origen y destino no pueden ser iguales', 400);
                }
            }

            if (isset($data['departure']) || isset($data['arrival'])) {
                $departure = $data['departure'] ?? $flight->departure;
                $arrival = $data['arrival'] ?? $flight->arrival;
                if (strtotime($arrival) <= strtotime($departure)) {
                    return $this->errorResponse($response, 'La fecha de llegada debe ser posterior a la fecha de salida', 400);
                }
            }

            if (isset($data['nave_id']) && $data['nave_id'] !== $flight->nave_id) {
                $aircraft = \Illuminate\Database\Capsule\Manager::table('naves')
                    ->where('id', $data['nave_id'])
                    ->first();
                if (!$aircraft) {
                    return $this->errorResponse($response, 'La nave especificada no existe', 404);
                }
            }

            $flight->update($data);

            return $this->successResponse($response, $flight);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $flight = Flight::find($args['id']);

            if (!$flight) {
                return $this->errorResponse($response, 'Vuelo no encontrado', 404);
            }

            $reservations = \Illuminate\Database\Capsule\Manager::table('reservations')
                ->where('flight_id', $flight->id)
                ->where('status', 'activa')
                ->count();

            if ($reservations > 0) {
                return $this->errorResponse($response, 'No se puede eliminar un vuelo que tiene reservas activas. Cancele las reservas primero.', 409);
            }

            $flight->delete();

            return $this->successResponse($response, ['message' => 'Vuelo eliminado']);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }
}
