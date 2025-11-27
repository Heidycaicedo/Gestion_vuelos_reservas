<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Reservation;
use App\Models\Flight;

class ReservationController
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
            $queryParams = $request->getQueryParams();
            $userId = $queryParams['user_id'] ?? null;

            $query = Reservation::query();

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $reservations = $query->get();

            return $this->successResponse($response, $reservations);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function listByUser(Request $request, Response $response, $args)
    {
        try {
            $userId = $args['id'];

            $reservations = Reservation::where('user_id', $userId)->get();

            return $this->successResponse($response, $reservations);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $userId = $request->getAttribute('user_id');

            if (empty($data['flight_id'])) {
                return $this->errorResponse($response, 'flight_id es requerido', 400);
            }

            $flight = Flight::find($data['flight_id']);

            if (!$flight) {
                return $this->errorResponse($response, 'El vuelo especificado no existe', 404);
            }

            $existingReservation = Reservation::where('user_id', $userId)
                ->where('flight_id', $data['flight_id'])
                ->where('status', 'activa')
                ->first();

            if ($existingReservation) {
                return $this->errorResponse($response, 'Ya tienes una reserva activa en este vuelo', 409);
            }

            $reservation = Reservation::create([
                'user_id' => $userId,
                'flight_id' => $data['flight_id'],
                'status' => 'activa'
            ]);

            return $this->successResponse($response, $reservation, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function cancel(Request $request, Response $response, $args)
    {
        try {
            $reservation = Reservation::find($args['id']);

            if (!$reservation) {
                return $this->errorResponse($response, 'Reserva no encontrada', 404);
            }

            if ($reservation->status !== 'activa') {
                return $this->errorResponse($response, 'No se puede cancelar una reserva que no está activa', 409);
            }

            $reservation->status = 'cancelada';
            $reservation->save();

            return $this->successResponse($response, ['message' => 'Reserva cancelada correctamente', 'data' => $reservation]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }
}
