<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;

class UserController
{
    public function list(Request $request, Response $response)
    {
        try {
            $users = User::all();
            $response->getBody()->write(json_encode(['success' => true, 'data' => $users]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function show(Request $request, Response $response, $args)
    {
        try {
            $user = User::find($args['id']);

            if (!$user) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Usuario no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $user]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $user = User::find($args['id']);

            if (!$user) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Usuario no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $user->update($data);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $user]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateRole(Request $request, Response $response, $args)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $user = User::find($args['id']);

            if (!$user) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            if (empty($data['role'])) {
                return $this->errorResponse($response, 'Rol requerido', 400);
            }

            if (!in_array($data['role'], ['administrador', 'gestor'])) {
                return $this->errorResponse($response, 'Rol inválido', 400);
            }

            $user->role = $data['role'];
            $user->save();

            return $this->successResponse($response, $user);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al actualizar rol', 500);
        }
    }

    private function successResponse(Response $response, $data, $statusCode = 200)
    {
        $response->getBody()->write(json_encode(['success' => true, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }

    private function errorResponse(Response $response, $message, $statusCode = 400)
    {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}