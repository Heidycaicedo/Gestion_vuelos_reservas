<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\Session;

class AuthController
{
    public function register(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            // Validar datos
            if (empty($data['email']) || empty($data['password']) || empty($data['nombre'])) {
                return $this->errorResponse($response, 'Datos incompletos', 400);
            }

            // Crear usuario
            $user = User::create([
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'rol' => $data['rol'] ?? 'gestor',
            ]);

            return $this->successResponse($response, ['usuario_id' => $user->id], 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    public function login(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody(), true);

            if (empty($data['email']) || empty($data['password'])) {
                return $this->errorResponse($response, 'Email y contraseña requeridos', 400);
            }

            $user = User::where('email', $data['email'])->first();

            if (!$user || !password_verify($data['password'], $user->password)) {
                return $this->errorResponse($response, 'Credenciales inválidas', 401);
            }

            // Generar token
            $token = bin2hex(random_bytes(32));

            Session::create([
                'usuario_id' => $user->id,
                'token' => $token,
                'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ]);

            return $this->successResponse($response, [
                'token' => $token,
                'usuario_id' => $user->id,
                'rol' => $user->rol,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
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

            Session::where('token', $token)->delete();

            return $this->successResponse($response, ['mensaje' => 'Sesión cerrada']);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
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

            $session = Session::where('token', $token)
                ->where('fecha_expiracion', '>', date('Y-m-d H:i:s'))
                ->first();

            if (!$session) {
                return $this->errorResponse($response, 'Token inválido o expirado', 401);
            }

            $user = User::find($session->usuario_id);

            return $this->successResponse($response, [
                'usuario_id' => $user->id,
                'rol' => $user->rol,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
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
