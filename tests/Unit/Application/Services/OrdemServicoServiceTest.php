<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\OrdemServicoNotificacaoService;
use App\Application\Services\OrdemServicoService;
use App\Application\Services\SistemaNotificacaoService;
use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Mecanico\Repositories\MecanicoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class OrdemServicoServiceTest extends TestCase
{
    private OrdemServicoRepositoryInterface&MockObject $repository;
    private ClienteRepositoryInterface&MockObject $clienteRepository;
    private VeiculoRepositoryInterface&MockObject $veiculoRepository;
    private MecanicoRepositoryInterface&MockObject $mecanicoRepository;
    private OrdemServicoNotificacaoService&MockObject $notificacaoService;
    private SistemaNotificacaoService&MockObject $sistemaNotificacaoService;
    private OrdemServicoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository                = $this->createMock(OrdemServicoRepositoryInterface::class);
        $this->clienteRepository         = $this->createMock(ClienteRepositoryInterface::class);
        $this->veiculoRepository         = $this->createMock(VeiculoRepositoryInterface::class);
        $this->mecanicoRepository        = $this->createMock(MecanicoRepositoryInterface::class);
        $this->notificacaoService        = $this->createMock(OrdemServicoNotificacaoService::class);
        $this->sistemaNotificacaoService = $this->createMock(SistemaNotificacaoService::class);

        $this->service = new OrdemServicoService(
            $this->repository,
            $this->clienteRepository,
            $this->veiculoRepository,
            $this->mecanicoRepository,
            $this->notificacaoService,
            $this->sistemaNotificacaoService,
        );
    }

    private function makeOrdemServico(int $id = 1, string $status = StatusOrdem::RECEBIDA): OrdemServico
    {
        return new OrdemServico(
            id: $id,
            clienteId: 1,
            veiculoId: 1,
            mecanicoId: null,
            status: StatusOrdem::from($status),
            descricaoProblema: 'Carro não liga',
            diagnostico: null,
            valorTotal: null,
            iniciadaEm: null,
            criadaEm: new \DateTimeImmutable(),
        );
    }

    private function makeCliente(int $id = 1): Cliente
    {
        return new Cliente(
            id: $id,
            nome: 'João Silva',
            tipo: 'pf',
            documento: new Cpf('529.982.247-25'),
            telefone: '11999999999',
            email: 'joao@email.com',
            criadoEm: new \DateTimeImmutable(),
        );
    }

    private function gerarToken(int $ordemId, string $acao, ?int $exp = null): string
    {
        $reflection = new \ReflectionClass($this->service);

        $base64Encode = $reflection->getMethod('base64UrlEncode');
        $base64Encode->setAccessible(true);

        $secretMethod = $reflection->getMethod('tokenSecret');
        $secretMethod->setAccessible(true);

        $payload = json_encode([
            'ordem_id' => $ordemId,
            'acao'     => $acao,
            'exp'      => $exp ?? time() + 86400,
        ], JSON_UNESCAPED_SLASHES);

        $encodedPayload = $base64Encode->invoke($this->service, $payload);
        $signature      = hash_hmac('sha256', $encodedPayload, $secretMethod->invoke($this->service));

        return $encodedPayload . '.' . $signature;
    }

    public function test_cria_os_com_cliente_id(): void
    {
        $os = $this->makeOrdemServico();

        $this->veiculoRepository->method('existsByIdAndClienteId')->willReturn(true);
        $this->repository->expects($this->once())->method('save')->willReturn($os);

        $resultado = $this->service->criar([
            'cliente_id'         => 1,
            'veiculo_id'         => 1,
            'descricao_problema' => 'Carro não liga',
        ]);

        $this->assertInstanceOf(OrdemServico::class, $resultado);
        $this->assertTrue($resultado->getStatus()->equals(StatusOrdem::RECEBIDA));
    }

    public function test_cria_os_com_cliente_documento(): void
    {
        $cliente = $this->makeCliente(2);
        $os      = $this->makeOrdemServico();

        $this->clienteRepository->method('findByDocumentoTipo')->willReturn($cliente);
        $this->veiculoRepository->method('existsByIdAndClienteId')->willReturn(true);
        $this->repository->expects($this->once())->method('save')->willReturn($os);

        $resultado = $this->service->criar([
            'cliente_documento'  => '529.982.247-25',
            'veiculo_id'         => 1,
            'descricao_problema' => 'Carro não liga',
        ]);

        $this->assertInstanceOf(OrdemServico::class, $resultado);
    }

    public function test_falha_sem_cliente_valido(): void
    {
        $this->expectException(\DomainException::class);

        $this->service->criar([
            'veiculo_id'         => 1,
            'descricao_problema' => 'Carro não liga',
        ]);
    }

    public function test_aprovar_publico_com_token_valido(): void
    {
        $os    = $this->makeOrdemServico(1, StatusOrdem::AGUARDANDO_APROVACAO);
        $token = $this->gerarToken(1, 'aprovar');

        $this->repository->method('findById')->willReturn($os);
        $this->repository->expects($this->once())->method('save')->willReturn($os);

        $resultado = $this->service->aprovarPublico(1, $token);

        $this->assertInstanceOf(OrdemServico::class, $resultado);
        $this->assertTrue($resultado->getStatus()->equals(StatusOrdem::APROVADA));
    }

    public function test_aprovar_publico_com_token_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->aprovarPublico(1, 'token.invalido');
    }

    public function test_aprovar_publico_com_token_expirado_lanca_excecao(): void
    {
        $token = $this->gerarToken(1, 'aprovar', time() - 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expirado');

        $this->service->aprovarPublico(1, $token);
    }
}
