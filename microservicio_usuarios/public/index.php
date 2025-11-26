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
$app->post('/api/usuarios/registrar', \App\Controllers\AuthController::class . ':register');
$app->post('/api/usuarios/login', \App\Controllers\AuthController::class . ':login');

// Rutas Protegidas
$app->group('', function ($app) {
    $app->post('/api/usuarios/logout', \App\Controllers\AuthController::class . ':logout');
    $app->post('/api/usuarios/validar-token', \App\Controllers\AuthController::class . ':validateToken');
    
    // Rutas solo para Administrador
    $app->group('', function ($app) {
        $app->get('/api/usuarios', \App\Controllers\UserController::class . ':list');
        $app->get('/api/usuarios/{id}', \App\Controllers\UserController::class . ':show');
        $app->put('/api/usuarios/{id}', \App\Controllers\UserController::class . ':update');
        $app->put('/api/usuarios/{id}/rol', \App\Controllers\UserController::class . ':updateRole');
    })->add(\App\Middleware\AdminMiddleware::class);
})->add(\App\Middleware\AuthMiddleware::class);

$app->run();
