<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;

class AuthController
{
    public function register(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return $this->errorResponse($response, 'Nombre, correo y contraseña son requeridos', 400);
            }

            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                return $this->errorResponse($response, 'El correo ya está registrado', 409);
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'role' => $data['role'] ?? 'gestor',
            ]);

            return $this->successResponse($response, ['user_id' => $user->id, 'message' => 'Usuario registrado exitosamente'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 500);
        }
    }

    public function login(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            if (empty($data['email']) || empty($data['password'])) {
                return $this->errorResponse($response, 'Correo y contraseña son requeridos', 400);
            }

            $user = User::where('email', $data['email'])->first();

            if (!$user || !password_verify($data['password'], $user->password)) {
                return $this->errorResponse($response, 'Correo o contraseña inválidos', 401);
            }

            $token = bin2hex(random_bytes(32));
            $user->token = $token;
            $user->save();

            return $this->successResponse($response, [
                'token' => $token,
                'user_id' => $user->id,
                'role' => $user->role,
                'message' => 'Inicio de sesión exitoso',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 500);
        }
    }

    public function logout(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $token = $data['token'] ?? null;

            if (!$token) {
                return $this->errorResponse($response, 'Token requerido', 400);
            }

            $user = User::where('token', $token)->first();
            if ($user) {
                $user->token = null;
                $user->save();
            }

            return $this->successResponse($response, ['message' => 'Sesión cerrada correctamente']);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 500);
        }
    }

    public function validateToken(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);
            $token = $data['token'] ?? null;

            if (!$token) {
                return $this->errorResponse($response, 'Token requerido', 400);
            }

            $user = User::where('token', $token)->first();

            if (!$user) {
                return $this->errorResponse($response, 'Token inválido o expirado', 401);
            }

            return $this->successResponse($response, [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error interno del servidor', 500);
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
