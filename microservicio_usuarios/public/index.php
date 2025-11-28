<?php

use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/../vendor/autoload.php';

// Configurar Eloquent ORM
$capsule = new Capsule;
$config = require __DIR__ . '/../config/database.php';

$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$app = AppFactory::create();
// CORS middleware (development convenience)
$app->add(function (Request $request, RequestHandler $handler) {
    // Handle preflight
    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $resp = new \Slim\Psr7\Response();
        return $resp
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withStatus(200);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Controlador por defecto de errores (500) en español
$defaultErrorHandler = function (Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $payload = ['success' => false, 'error' => 'Error interno del servidor'];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
};

// Controlador para rutas no encontradas (404) en español
$notFoundHandler = function (Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $payload = ['success' => false, 'error' => 'Recurso no encontrado'];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
};

$errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);
$errorMiddleware->setErrorHandler(Slim\Exception\HttpNotFoundException::class, $notFoundHandler);

// Rutas Públicas
$app->post('/api/auth/login', function (Request $request, Response $response) {
    $controller = new AuthController();
    return $controller->login($request, $response);
});

// Registro público (según requisito: registro debe ser público)
$app->post('/api/auth/register', function (Request $request, Response $response) {
    $controller = new AuthController();
    return $controller->register($request, $response);
});

// Rutas Protegidas
$app->group('', function ($app) {
    $app->post('/api/auth/logout', function (Request $request, Response $response) {
        $controller = new AuthController();
        return $controller->logout($request, $response);
    });
    $app->post('/api/auth/validate', function (Request $request, Response $response) {
        $controller = new AuthController();
        return $controller->validateToken($request, $response);
    });
    
    // Rutas solo para Administrador
    $app->group('', function ($app) {
        $app->get('/api/users', function (Request $request, Response $response) {
            $controller = new UserController();
            return $controller->list($request, $response);
        });
        $app->get('/api/users/{id}', function (Request $request, Response $response, array $args) {
            $controller = new UserController();
            return $controller->show($request, $response, $args);
        });
        $app->put('/api/users/{id}', function (Request $request, Response $response, array $args) {
            $controller = new UserController();
            return $controller->update($request, $response, $args);
        });
        $app->put('/api/users/{id}/role', function (Request $request, Response $response, array $args) {
            $controller = new UserController();
            return $controller->updateRole($request, $response, $args);
        });
    })->add(new AdminMiddleware());
})->add(new AuthMiddleware());

$app->run();
