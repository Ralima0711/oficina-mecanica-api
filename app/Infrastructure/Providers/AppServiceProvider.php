<?php

namespace App\Infrastructure\Providers;

use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\Notificacao\Repositories\NotificacaoRepositoryInterface;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Peca\Repositories\PecaRepositoryInterface;
use App\Domain\Insumo\Repositories\InsumoRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Servico\Repositories\ServicoRepositoryInterface;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use App\Domain\Mecanico\Repositories\MecanicoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificacaoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentServicoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrdemServicoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentClienteRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPecaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInsumoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentVeiculoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentMecanicoRepository;
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
            NotificacaoRepositoryInterface::class,
            EloquentNotificacaoRepository::class
        );

        $this->app->bind(
            ClienteRepositoryInterface::class,
            EloquentClienteRepository::class
        );

        $this->app->bind(
            PecaRepositoryInterface::class,
            EloquentPecaRepository::class
        );

        $this->app->bind(
            InsumoRepositoryInterface::class,
            EloquentInsumoRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            VeiculoRepositoryInterface::class,
            EloquentVeiculoRepository::class
        );

        $this->app->bind(
            MecanicoRepositoryInterface::class,
            EloquentMecanicoRepository::class
        );

        $this->app->bind(
            ServicoRepositoryInterface::class,
            EloquentServicoRepository::class
        );
    }

    public function boot(): void {}
}
