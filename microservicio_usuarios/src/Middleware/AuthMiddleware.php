<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Models\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $headers = $request->getHeaders();
        $token = null;

        // Obtener token de Authorization header o body
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'][0];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            $body = json_decode((string)$request->getBody(), true);
            $token = $body['token'] ?? null;
        }

        if (!$token) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Token requerido'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Validar token
        $session = Session::where('token', $token)
            ->where('fecha_expiracion', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$session) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Token inválido o expirado'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Agregar usuario y token al request para uso posterior
        $request = $request->withAttribute('usuario_id', $session->usuario_id);
        $request = $request->withAttribute('token', $token);

        return $handler->handle($request);
    }
}
