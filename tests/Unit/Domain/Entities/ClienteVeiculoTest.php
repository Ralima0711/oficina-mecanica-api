<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Cliente\ValueObjects\Cnpj;
use App\Domain\Veiculo\Entities\Veiculo;
use App\Domain\Veiculo\ValueObjects\Placa;
use PHPUnit\Framework\TestCase;

class ClienteVeiculoTest extends TestCase
{
    // ── Cliente ───────────────────────────────────────────────────

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

    public function test_cliente_getters_retornam_valores_corretos(): void
    {
        $cliente = $this->makeCliente(1, 'pf');

        $this->assertEquals(1, $cliente->getId());
        $this->assertEquals('João Silva', $cliente->getNome());
        $this->assertEquals('pf', $cliente->getTipo());
        $this->assertEquals('11999999999', $cliente->getTelefone());
        $this->assertEquals('joao@email.com', $cliente->getEmail());
        $this->assertInstanceOf(Cpf::class, $cliente->getDocumento());
    }

    public function test_cliente_pj_tem_cnpj(): void
    {
        $cliente = $this->makeCliente(1, 'pj');

        $this->assertEquals('pj', $cliente->getTipo());
        $this->assertInstanceOf(Cnpj::class, $cliente->getDocumento());
    }

    public function test_cliente_sem_id_retorna_null(): void
    {
        $cliente = new Cliente(
            id: null,
            nome: 'Novo Cliente',
            tipo: 'pf',
            documento: new Cpf('529.982.247-25'),
            telefone: '11999999999',
            email: 'novo@email.com',
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertNull($cliente->getId());
    }

    public function test_cliente_atualizar_muda_todos_os_campos(): void
    {
        $cliente = $this->makeCliente(1, 'pf');
        $novoCpf = new Cpf('529.982.247-25');

        $cliente->atualizar(
            'Maria Silva',
            'pf',
            $novoCpf,
            '11988888888',
            'maria@email.com'
        );

        $this->assertEquals('Maria Silva', $cliente->getNome());
        $this->assertEquals('pf', $cliente->getTipo());
        $this->assertEquals('11988888888', $cliente->getTelefone());
        $this->assertEquals('maria@email.com', $cliente->getEmail());
    }

    public function test_cliente_atualizar_de_pf_para_pj(): void
    {
        $cliente = $this->makeCliente(1, 'pf');
        $cnpj = new Cnpj('11.222.333/0001-81');

        $cliente->atualizar(
            'Empresa Ltda',
            'pj',
            $cnpj,
            '1133334444',
            'empresa@email.com'
        );

        $this->assertEquals('pj', $cliente->getTipo());
        $this->assertInstanceOf(Cnpj::class, $cliente->getDocumento());
    }

    public function test_cliente_json_serialize_retorna_estrutura_correta(): void
    {
        $cliente = $this->makeCliente(1, 'pf');
        $array = $cliente->jsonSerialize();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nome', $array);
        $this->assertArrayHasKey('tipo', $array);
        $this->assertArrayHasKey('documento', $array);
        $this->assertArrayHasKey('telefone', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('criado_em', $array);
    }

    public function test_cliente_json_serialize_documento_como_string(): void
    {
        $cliente = $this->makeCliente(1, 'pf');
        $array = $cliente->jsonSerialize();

        $this->assertIsString($array['documento']);
        $this->assertEquals('529.982.247-25', $array['documento']);
    }

    public function test_cliente_json_serialize_pj_documento_cnpj(): void
    {
        $cliente = $this->makeCliente(1, 'pj');
        $array = $cliente->jsonSerialize();

        $this->assertIsString($array['documento']);
        $this->assertEquals('11.222.333/0001-81', $array['documento']);
    }

    // ── Veiculo ───────────────────────────────────────────────────

    private function makeVeiculo(int $id = 1): Veiculo
    {
        return new Veiculo(
            id: $id,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
            ano: 2020,
            cor: 'Prata',
            criadoEm: new \DateTimeImmutable(),
        );
    }

    public function test_veiculo_getters_retornam_valores_corretos(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $this->assertEquals(1, $veiculo->getId());
        $this->assertEquals(10, $veiculo->getClienteId());
        $this->assertEquals('ABC1234', (string) $veiculo->getPlaca());
        $this->assertEquals('Toyota', $veiculo->getMarca());
        $this->assertEquals('Corolla', $veiculo->getModelo());
        $this->assertEquals(2020, $veiculo->getAno());
        $this->assertEquals('Prata', $veiculo->getCor());
        $this->assertNotNull($veiculo->getCriadoEm());
    }

    public function test_veiculo_sem_id_retorna_null(): void
    {
        $veiculo = new Veiculo(
            id: null,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Honda',
            modelo: 'Civic',
            ano: 2021,
            cor: null,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertNull($veiculo->getId());
    }

    public function test_veiculo_sem_cor_retorna_null(): void
    {
        $veiculo = new Veiculo(
            id: 1,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Honda',
            modelo: 'Civic',
            ano: 2021,
            cor: null,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertNull($veiculo->getCor());
    }

    public function test_veiculo_atualizar_muda_todos_os_campos(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $veiculo->atualizar(
            20,
            new Placa('XYZ9876'),
            'Honda',
            'Fit',
            2019,
            'Branco'
        );

        $this->assertEquals(20, $veiculo->getClienteId());
        $this->assertEquals('XYZ9876', (string) $veiculo->getPlaca());
        $this->assertEquals('Honda', $veiculo->getMarca());
        $this->assertEquals('Fit', $veiculo->getModelo());
        $this->assertEquals(2019, $veiculo->getAno());
        $this->assertEquals('Branco', $veiculo->getCor());
    }

    public function test_veiculo_atualizar_cor_para_null(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $veiculo->atualizar(
            10,
            new Placa('ABC1234'),
            'Toyota',
            'Corolla',
            2020,
            null
        );

        $this->assertNull($veiculo->getCor());
    }

    public function test_veiculo_placa_mercosul(): void
    {
        $veiculo = new Veiculo(
            id: 1,
            clienteId: 10,
            placa: new Placa('ABC1D23'),
            marca: 'Ford',
            modelo: 'Ka',
            ano: 2022,
            cor: 'Azul',
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertEquals('ABC1D23', (string) $veiculo->getPlaca());
    }

    public function test_veiculo_json_serialize_retorna_estrutura_correta(): void
    {
        $veiculo = $this->makeVeiculo(1);
        $array = $veiculo->jsonSerialize();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('cliente_id', $array);
        $this->assertArrayHasKey('placa', $array);
        $this->assertArrayHasKey('marca', $array);
        $this->assertArrayHasKey('modelo', $array);
        $this->assertArrayHasKey('ano', $array);
        $this->assertArrayHasKey('cor', $array);
        $this->assertArrayHasKey('criado_em', $array);
    }

    public function test_veiculo_json_serialize_placa_como_string(): void
    {
        $veiculo = $this->makeVeiculo(1);
        $array = $veiculo->jsonSerialize();

        $this->assertIsString($array['placa']);
        $this->assertEquals('ABC1234', $array['placa']);
    }

    public function test_veiculo_json_serialize_cor_null(): void
    {
        $veiculo = new Veiculo(
            id: 1,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
            ano: 2020,
            cor: null,
            criadoEm: new \DateTimeImmutable(),
        );

        $array = $veiculo->jsonSerialize();
        $this->assertNull($array['cor']);
    }
}
