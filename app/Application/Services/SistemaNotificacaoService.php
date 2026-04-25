<?php

namespace App\Application\Services;

use App\Domain\Notificacao\Repositories\NotificacaoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SistemaNotificacaoService
{
    public const TIPO_OS_RECEBIDA = 'OS_RECEBIDA';
    public const TIPO_OS_RECEBIDA_LEMBRETE_24H = 'OS_RECEBIDA_LEMBRETE_24H';
    public const TIPO_OS_AGUARDANDO_APROVACAO = 'OS_AGUARDANDO_APROVACAO';
    public const TIPO_OS_APROVADA = 'OS_APROVADA';
    public const TIPO_OS_FINALIZADA = 'OS_FINALIZADA';

    public function __construct(
        private NotificacaoRepositoryInterface $notificacaoRepository,
        private UserRepositoryInterface $userRepository,
        private OrdemServicoRepositoryInterface $ordemServicoRepository,
    ) {}

    public function listarMinhas(int $userId, int $perPage = 20): array
    {
        return $this->notificacaoRepository->findByUser($userId, $perPage);
    }

    public function marcarComoLida(int $notificacaoId, int $userId)
    {
        $notificacao = $this->notificacaoRepository->findByIdAndUser($notificacaoId, $userId);

        if (!$notificacao) {
            throw new \RuntimeException("Notificação #{$notificacaoId} não encontrada para o usuário logado.");
        }

        $marcada = $this->notificacaoRepository->markAsRead($notificacaoId);

        if (!$marcada) {
            throw new \RuntimeException("Notificação #{$notificacaoId} não encontrada.");
        }

        return $marcada;
    }

    public function notificarOsRecebidaParaMecanicos(OrdemServico $ordem): void
    {
        $this->notificarPorRole($ordem, 'mecanico', self::TIPO_OS_RECEBIDA);
    }

    public function notificarOsAguardandoAprovacaoParaAtendentes(OrdemServico $ordem): void
    {
        $this->notificarPorRole($ordem, 'atendente', self::TIPO_OS_AGUARDANDO_APROVACAO);
    }

    public function notificarOsFinalizadaParaAtendentes(OrdemServico $ordem): void
    {
        $this->notificarPorRole($ordem, 'atendente', self::TIPO_OS_FINALIZADA);
    }

    public function notificarMecanicoResponsavel(OrdemServico $ordem, string $tipo): void
    {
        $ordemId = $ordem->getId();
        $mecanicoId = $ordem->getMecanicoId();

        if ($ordemId === null || $mecanicoId === null) {
            return;
        }

        $userId = DB::table('mecanicos')
            ->where('id', $mecanicoId)
            ->value('user_id');

        if ($userId === null) {
            return;
        }

        $this->notificacaoRepository->createForUsers($ordemId, [(int) $userId], $tipo);
    }

    public function processarLembretesOsRecebidasSemDiagnostico(): int
    {
        $ordens = $this->ordemServicoRepository->findRecebidas24Horas();
        $total = 0;

        foreach ($ordens as $ordem) {
            try {
                $ordemId = $ordem->getId();

                if ($ordemId === null) {
                    continue;
                }

                $ultimaNotificacao = $this->notificacaoRepository->findLastByOrdemAndTipo(
                    $ordemId,
                    self::TIPO_OS_RECEBIDA_LEMBRETE_24H
                );

                if ($ultimaNotificacao !== null && $ultimaNotificacao->getCriadaEm() > now()->subHours(24)->toDateTimeImmutable()) {
                    continue;
                }

                $usuarios = $this->userRepository->findByRole('mecanico');
                $userIds = $this->extrairUserIds($usuarios);

                if ($userIds === []) {
                    continue;
                }

                $total += $this->notificacaoRepository->createForUsers(
                    $ordemId,
                    $userIds,
                    self::TIPO_OS_RECEBIDA_LEMBRETE_24H
                );
            } catch (\Throwable $e) {
                Log::error('Falha ao processar lembrete de OS recebida sem diagnóstico.', [
                    'ordem_servico_id' => $ordem->getId(),
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return $total;
    }

    private function notificarPorRole(OrdemServico $ordem, string $role, string $tipo): void
    {
        $ordemId = $ordem->getId();

        if ($ordemId === null) {
            return;
        }

        $usuarios = $this->userRepository->findByRole($role);
        $userIds = $this->extrairUserIds($usuarios);

        if ($userIds === []) {
            return;
        }

        $this->notificacaoRepository->createForUsers($ordemId, $userIds, $tipo);
    }

    private function extrairUserIds(array $usuarios): array
    {
        $ids = [];

        foreach ($usuarios as $usuario) {
            $id = $usuario->getId();

            if ($id !== null && $id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
