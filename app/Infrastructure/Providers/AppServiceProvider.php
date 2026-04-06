<?php

namespace App\Infrastructure\Providers;

use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrdemServicoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentClienteRepository;
use Illuminate\Support\ServiceProvider;

/**
 * CAMADA DE INFRAESTRUTURA — Service Provider
 * Registra as implementações concretas para as interfaces do Domínio.
 * É aqui que a Injeção de Dependência conecta tudo.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domínio define a interface → Infraestrutura fornece a implementação
        $this->app->bind(
            OrdemServicoRepositoryInterface::class,
            EloquentOrdemServicoRepository::class
        );

        $this->app->bind(
            ClienteRepositoryInterface::class,
            EloquentClienteRepository::class
        );
    }

    public function boot(): void {}
}
