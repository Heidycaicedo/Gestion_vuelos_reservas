<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Reservation;
use App\Models\Flight;

class ReservationController
{
    // 4.2 Consultar todas las reservas (con filtros opcionales)
    public function list(Request $request, Response $response)
    {
        try {
            $queryParams = $request->getQueryParams();
            $usuarioId = $queryParams['usuario_id'] ?? null;

            $query = Reservation::query();

            // 4.3 Filtrar por usuario si se especifica
            if ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            }

            $reservations = $query->get();

            $response->getBody()->write(json_encode(['success' => true, 'data' => $reservations]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 4.3 Consultar reservas por usuario específico
    public function listByUser(Request $request, Response $response, $args)
    {
        try {
            $usuarioId = $args['id'];

            $reservations = Reservation::where('usuario_id', $usuarioId)->get();

            $response->getBody()->write(json_encode(['success' => true, 'data' => $reservations]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 4.1 Crear una nueva reserva con validaciones
    public function create(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $usuarioId = $request->getAttribute('usuario_id');

            // Validar datos requeridos
            if (empty($data['vuelo_id']) || empty($data['numero_asiento'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'vuelo_id y numero_asiento son requeridos'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 4.5 Verificar que el vuelo existe
            $flight = Flight::find($data['vuelo_id']);

            if (!$flight) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'El vuelo especificado no existe'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Verificar que el asiento no esté ya reservado
            $existingReservation = Reservation::where('vuelo_id', $data['vuelo_id'])
                ->where('numero_asiento', $data['numero_asiento'])
                ->where('estado', 'confirmada')
                ->first();

            if ($existingReservation) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'El asiento ya está reservado en este vuelo'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Verificar que hay asientos disponibles
            if ($flight->asientos_disponibles <= 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No hay asientos disponibles en este vuelo'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Crear la reserva
            $reservation = Reservation::create([
                'usuario_id' => $usuarioId,
                'vuelo_id' => $data['vuelo_id'],
                'numero_asiento' => $data['numero_asiento'],
                'estado' => 'confirmada'
            ]);

            // Reducir asientos disponibles
            $flight->asientos_disponibles -= 1;
            $flight->save();

            $response->getBody()->write(json_encode(['success' => true, 'data' => $reservation]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 4.4 Cancelar una reserva
    public function cancel(Request $request, Response $response, $args)
    {
        try {
            $reservation = Reservation::find($args['id']);

            if (!$reservation) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Reserva no encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Verificar que la reserva está confirmada
            if ($reservation->estado !== 'confirmada') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No se puede cancelar una reserva que no está confirmada'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Cambiar estado a cancelada
            $reservation->estado = 'cancelada';
            $reservation->save();

            // Liberar el asiento (incrementar asientos disponibles)
            $flight = Flight::find($reservation->vuelo_id);
            if ($flight) {
                $flight->asientos_disponibles += 1;
                $flight->save();
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Reserva cancelada correctamente',
                'data' => $reservation
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
