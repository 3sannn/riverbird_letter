<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use App\Http\Router;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'store']);

$router->get('/clients', [ClientController::class, 'index']);
$router->get('/clients/create', [ClientController::class, 'create']);
$router->post('/clients', [ClientController::class, 'store']);
$router->get('/clients/edit', [ClientController::class, 'edit']);
$router->post('/clients/update', [ClientController::class, 'update']);
$router->post('/clients/archive', [ClientController::class, 'archive']);

$router->get('/documents', [DocumentController::class, 'index']);
$router->get('/documents/create', [DocumentController::class, 'create']);
$router->get('/documents/show', [DocumentController::class, 'show']);
$router->get('/documents/edit', [DocumentController::class, 'edit']);
$router->post('/documents/save', [DocumentController::class, 'save']);
$router->post('/documents/issue', [DocumentController::class, 'issue']);
$router->post('/documents/duplicate', [DocumentController::class, 'duplicate']);
$router->post('/documents/payment', [DocumentController::class, 'addPayment']);
$router->get('/documents/pdf', [DocumentController::class, 'pdf']);
$router->post('/documents/archive', [DocumentController::class, 'archive']);

$router->get('/files/download', [FileController::class, 'download']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users', [UserController::class, 'store']);
$router->post('/users/toggle', [UserController::class, 'toggle']);

return $router;
