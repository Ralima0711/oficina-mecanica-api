<?php

use App\Interface\Http\Controllers\AuthController;
use App\Interface\Http\Controllers\OrdemServicoController;
use App\Interface\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Oficina Mecânica
|--------------------------------------------------------------------------
*/

// Autenticação (pública)
Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/logout',  [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me',       [AuthController::class, 'me'])->middleware('auth:api');
});

// Recursos protegidos por JWT
Route::middleware('auth:api')->group(function () {
    Route::apiResource('clientes',       ClienteController::class);
});

Route::middleware(['auth:api', 'admin'])->group(function () {
    // Ajusta o parametro para {id}, mantendo o contrato esperado da API.
    Route::apiResource('ordens-servico', OrdemServicoController::class)
        ->parameters(['ordens-servico' => 'id']);

    // Transições de estado da OS (regras de negócio)
    Route::patch('ordens-servico/{id}/iniciar',   [OrdemServicoController::class, 'iniciar']);
    Route::patch('ordens-servico/{id}/concluir',  [OrdemServicoController::class, 'concluir']);
    Route::patch('ordens-servico/{id}/cancelar',  [OrdemServicoController::class, 'cancelar']);
});
