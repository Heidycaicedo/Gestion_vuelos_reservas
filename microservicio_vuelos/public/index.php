<?php

use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

// Configurar Eloquent ORM
$capsule = new Capsule;
$config = require __DIR__ . '/../config/database.php';

$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$app = AppFactory::create();

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

// Rutas Públicas para listar vuelos
$app->get('/api/vuelos', \App\Controllers\FlightController::class . ':list');

// Rutas Protegidas
$app->group('', function ($app) {
    // Rutas protegidas para gestores
    $app->group('', function ($app) {
        $app->post('/api/reservas', \App\Controllers\ReservationController::class . ':create');
        $app->delete('/api/reservas/{id}', \App\Controllers\ReservationController::class . ':cancel');
    })->add(\App\Middleware\GestorMiddleware::class);

    // Rutas solo para Administrador
    $app->group('', function ($app) {
        $app->get('/api/vuelos/{id}', \App\Controllers\FlightController::class . ':show');
        $app->post('/api/vuelos', \App\Controllers\FlightController::class . ':create');
        $app->put('/api/vuelos/{id}', \App\Controllers\FlightController::class . ':update');
        $app->delete('/api/vuelos/{id}', \App\Controllers\FlightController::class . ':delete');

        $app->get('/api/naves', \App\Controllers\AircraftController::class . ':list');
        $app->get('/api/naves/{id}', \App\Controllers\AircraftController::class . ':show');
        $app->post('/api/naves', \App\Controllers\AircraftController::class . ':create');
        $app->put('/api/naves/{id}', \App\Controllers\AircraftController::class . ':update');
        $app->delete('/api/naves/{id}', \App\Controllers\AircraftController::class . ':delete');
    })->add(\App\Middleware\AdminMiddleware::class);

    // Reservas - Listar con autenticación
    $app->get('/api/reservas', \App\Controllers\ReservationController::class . ':list');
    $app->get('/api/reservas/usuario/{id}', \App\Controllers\ReservationController::class . ':listByUser');
})->add(\App\Middleware\AuthMiddleware::class);

$app->run();
