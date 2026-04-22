<?php

use App\Interface\Http\Controllers\AuthController;
use App\Interface\Http\Controllers\OrdemServicoController;
use App\Interface\Http\Controllers\ClienteController;
use App\Interface\Http\Controllers\VeiculoController;
use App\Interface\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Oficina Mecânica
|--------------------------------------------------------------------------
*/

// Autenticação (pública)
Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login'])->name('login');
    Route::post('/logout',  [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me',       [AuthController::class, 'me'])->middleware('auth:api');
});

// Recursos protegidos por JWT
Route::middleware('auth:api')->group(function () {
    Route::apiResource('usuarios',       UserController::class);
    Route::apiResource('clientes',       ClienteController::class);
    Route::apiResource('veiculos',       VeiculoController::class);
});

/*
|--------------------------------------------------------------------------
| Ordem de Servico - Rotas
|--------------------------------------------------------------------------
*/

// Listagem compartilhada para admin, atendente e mecanico.
Route::middleware(['auth:api', 'role:admin,atendente,mecanico'])->group(function () {
    Route::get('ordens-servico', [OrdemServicoController::class, 'index'])
        ->name('ordens-servico.index');
});

// Consulta individual compartilhada para admin e atendente.
Route::middleware(['auth:api', 'role:admin,atendente'])->group(function () {
    Route::post('ordens-servico', [OrdemServicoController::class, 'store'])
        ->name('ordens-servico.store');

    Route::get('ordens-servico/{id}', [OrdemServicoController::class, 'show'])
        ->name('ordens-servico.show');
});

// Rotas administrativas do recurso.
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('ordens-servico', OrdemServicoController::class)
        ->only(['update', 'destroy'])
        ->parameters(['ordens-servico' => 'id']);
});

// Atendente - Entrega da OS realizada.
Route::middleware(['auth:api', 'role:atendente'])->group(function () {
    Route::patch('ordens-servico/{id}/entregar', [OrdemServicoController::class, 'entregar'])
        ->name('ordens-servico.status.entregar');
});

//Mecanico - Transicoes tecnicas executadas.
Route::middleware(['auth:api', 'role:mecanico'])->prefix('ordens-servico')->name('ordens-servico.status.')->group(function () {
    Route::patch('{id}/iniciar-diagnostico', [OrdemServicoController::class, 'iniciarDiagnostico'])
        ->name('iniciar-diagnostico');

    // Fechamento do diagnostico com composicao de pecas/insumos e geracao interna do orcamento.
    Route::post('{id}/submeter-orcamento', [OrdemServicoController::class, 'submeterOrcamento'])
        ->name('submeter-orcamento');

    Route::patch('{id}/iniciar-execucao', [OrdemServicoController::class, 'iniciarExecucao'])
        ->name('iniciar-execucao');

    Route::patch('{id}/finalizar', [OrdemServicoController::class, 'finalizar'])
        ->name('finalizar');
});

//Cliente - Rotas publicas de aprovação/reprovação.
Route::prefix('public/ordens-servico')->name('public.ordens-servico.')->group(function () {
    Route::get('{id}/aprovar/{token}', [OrdemServicoController::class, 'aprovarPublico'])
        ->name('aprovar');

    Route::get('{id}/reprovar/{token}', [OrdemServicoController::class, 'reprovarPublico'])
        ->name('reprovar');
});
