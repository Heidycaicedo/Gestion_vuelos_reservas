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
            $users = User::all()->toArray();
            // No exponer tokens en la respuesta
            foreach ($users as &$u) {
                if (isset($u['token'])) {
                    unset($u['token']);
                }
                if (isset($u['password'])) {
                    unset($u['password']);
                }
            }
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
            $data = $user->toArray();
            if (isset($data['token'])) unset($data['token']);
            if (isset($data['password'])) unset($data['password']);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $data]));
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

            // Validar campos requeridos
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return $this->errorResponse($response, 'Nombre, email y contraseña son requeridos', 400);
            }

            // Validar rol
            $role = $data['role'] ?? 'gestor';
            if (!in_array($role, ['administrador', 'gestor'])) {
                return $this->errorResponse($response, 'Rol inválido', 400);
            }

            // Verificar si el email ya existe
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                return $this->errorResponse($response, 'El email ya está registrado', 409);
            }

            // Crear nuevo usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'role' => $role,
                'token' => null
            ]);

            // No exponer la contraseña
            $userData = $user->toArray();
            unset($userData['password']);
            unset($userData['token']);

            return $this->successResponse($response, $userData, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error al crear usuario: ' . $e->getMessage(), 500);
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

            // Si se actualiza la contraseña, hashearla
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            // Evitar que el campo `role` se actualice por esta ruta.
            if (isset($data['role'])) {
                unset($data['role']);
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

            $oldRole = $user->role;
            $user->role = $data['role'];

            // Obtener id del usuario que realiza la operación (viene del AuthMiddleware)
            $currentUserId = $request->getAttribute('user_id');

            // Si el administrador se cambia a sí mismo a otro rol, invalidar su token (cerrar sesión)
            if ($currentUserId && $currentUserId == $user->id && $user->role !== 'administrador') {
                $user->token = null;
                $user->save();
                return $this->successResponse($response, ['message' => 'Rol actualizado. Se ha cerrado la sesión del usuario (auto-democión).', 'user' => $user]);
            }

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