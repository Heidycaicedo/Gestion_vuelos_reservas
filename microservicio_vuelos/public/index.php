<?php

use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
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
$app->add(function ($request, RequestHandler $handler) {
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

// Añadir middleware de manejo de errores con mensajes en español
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Controlador por defecto de errores (500)
$defaultErrorHandler = function (Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $payload = ['success' => false, 'error' => 'Error interno del servidor'];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
};

// Controlador para rutas no encontradas (404)
$notFoundHandler = function (Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $payload = ['success' => false, 'error' => 'Recurso no encontrado'];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
};

$errorMiddleware->setDefaultErrorHandler($defaultErrorHandler);
$errorMiddleware->setErrorHandler(Slim\Exception\HttpNotFoundException::class, $notFoundHandler);

// Rutas Públicas para listar vuelos y naves
$app->get('/api/flights', \App\Controllers\FlightController::class . ':list');
$app->get('/api/aircraft', \App\Controllers\AircraftController::class . ':list');

// Rutas Protegidas
$app->group('', function ($app) {
    // Rutas accesibles para cualquier usuario autenticado (gestor/administrador)
    $app->get('/api/flights/{id}', \App\Controllers\FlightController::class . ':show');
    $app->get('/api/aircraft/{id}', \App\Controllers\AircraftController::class . ':show');
    // Rutas protegidas para gestores
    $app->group('', function ($app) {
        $app->post('/api/reservations', \App\Controllers\ReservationController::class . ':create');
        $app->delete('/api/reservations/{id}', \App\Controllers\ReservationController::class . ':cancel');
    })->add(\App\Middleware\GestorMiddleware::class);

    // Rutas solo para Administrador
    $app->group('', function ($app) {
        $app->post('/api/flights', \App\Controllers\FlightController::class . ':create');
        $app->put('/api/flights/{id}', \App\Controllers\FlightController::class . ':update');
        $app->delete('/api/flights/{id}', \App\Controllers\FlightController::class . ':delete');

        $app->post('/api/aircraft', \App\Controllers\AircraftController::class . ':create');
        $app->put('/api/aircraft/{id}', \App\Controllers\AircraftController::class . ':update');
        $app->delete('/api/aircraft/{id}', \App\Controllers\AircraftController::class . ':delete');
    })->add(\App\Middleware\AdminMiddleware::class);

    // Reservas - Listar con autenticación
    $app->get('/api/reservations', \App\Controllers\ReservationController::class . ':list');
    $app->get('/api/reservations/user/{id}', \App\Controllers\ReservationController::class . ':listByUser');
})->add(\App\Middleware\AuthMiddleware::class);

$app->run();
