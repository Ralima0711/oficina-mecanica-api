<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\ClienteService;
use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Cliente\ValueObjects\Cnpj;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ClienteServiceTest extends TestCase
{
    private ClienteRepositoryInterface&MockObject $repository;
    private ClienteService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ClienteRepositoryInterface::class);
        $this->service = new ClienteService($this->repository);
    }

    private function makeCliente(int $id = 1, string $tipo = 'pf'): Cliente
    {
        $documento = $tipo === 'pf'
            ? new Cpf('529.982.247-25')
            : new Cnpj('11.222.333/0001-81');

        return new Cliente(
            id: $id,
            nome: 'João Silva',
            tipo: $tipo,
            documento: $documento,
            telefone: '11999999999',
            email: 'joao@email.com',
            criadoEm: new \DateTimeImmutable(),
        );
    }

    // ── listarTodos ───────────────────────────────────────────────

    public function test_listar_todos_retorna_array(): void
    {
        $clientes = [$this->makeCliente(1), $this->makeCliente(2)];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($clientes);

        $result = $this->service->listarTodos();

        $this->assertCount(2, $result);
    }

    public function test_listar_todos_retorna_array_vazio_quando_nao_ha_clientes(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $result = $this->service->listarTodos();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── buscarPorId ───────────────────────────────────────────────

    public function test_buscar_por_id_retorna_cliente_existente(): void
    {
        $cliente = $this->makeCliente(1);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($cliente);

        $result = $this->service->buscarPorId(1);

        $this->assertSame($cliente, $result);
    }

    public function test_buscar_por_id_lanca_excecao_quando_nao_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cliente #99 não encontrado.');

        $this->service->buscarPorId(99);
    }

    // ── criar ─────────────────────────────────────────────────────

    public function test_criar_cliente_pf_com_sucesso(): void
    {
        $cliente = $this->makeCliente(1, 'pf');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($cliente);

        $result = $this->service->criar([
            'nome'      => 'João Silva',
            'tipo'      => 'pf',
            'documento' => '529.982.247-25',
            'telefone'  => '11999999999',
            'email'     => 'joao@email.com',
        ]);

        $this->assertInstanceOf(Cliente::class, $result);
        $this->assertEquals('pf', $result->getTipo());
    }

    public function test_criar_cliente_pj_com_sucesso(): void
    {
        $cliente = $this->makeCliente(1, 'pj');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($cliente);

        $result = $this->service->criar([
            'nome'      => 'Empresa Ltda',
            'tipo'      => 'pj',
            'documento' => '11.222.333/0001-81',
            'telefone'  => '1133334444',
            'email'     => 'empresa@email.com',
        ]);

        $this->assertInstanceOf(Cliente::class, $result);
    }

    public function test_criar_cliente_com_tipo_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de cliente inválido. Use pf ou pj.');

        $this->service->criar([
            'nome'      => 'Teste',
            'tipo'      => 'invalido',
            'documento' => '12345678901',
            'telefone'  => '11999999999',
            'email'     => 'teste@email.com',
        ]);
    }

    public function test_criar_com_cpf_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido.');

        $this->service->criar([
            'nome'      => 'Teste',
            'tipo'      => 'pf',
            'documento' => '00000000000',
            'telefone'  => '11999999999',
            'email'     => 'teste@email.com',
        ]);
    }

    public function test_criar_com_cnpj_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CNPJ inválido.');

        $this->service->criar([
            'nome'      => 'Empresa',
            'tipo'      => 'pj',
            'documento' => '00000000000000',
            'telefone'  => '1133334444',
            'email'     => 'empresa@email.com',
        ]);
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_cliente_existente_com_sucesso(): void
    {
        $cliente = $this->makeCliente(1, 'pf');

        $this->repository->method('findById')->willReturn($cliente);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($cliente);

        $result = $this->service->atualizar(1, [
            'nome'      => 'João Atualizado',
            'tipo'      => 'pf',
            'documento' => '529.982.247-25',
            'telefone'  => '11988888888',
            'email'     => 'novo@email.com',
        ]);

        $this->assertInstanceOf(Cliente::class, $result);
    }

    public function test_atualizar_cliente_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->atualizar(99, [
            'nome'      => 'X',
            'tipo'      => 'pf',
            'documento' => '529.982.247-25',
            'telefone'  => '11999999999',
            'email'     => 'x@x.com',
        ]);
    }

    // ── remover ───────────────────────────────────────────────────

    public function test_remover_cliente_existente_com_sucesso(): void
    {
        $cliente = $this->makeCliente(1);

        $this->repository->method('findById')->willReturn($cliente);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->remover(1);
    }

    public function test_remover_cliente_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->remover(99);
    }
}
