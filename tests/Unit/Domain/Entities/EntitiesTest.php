<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Peca\Entities\Peca;
use App\Domain\Insumo\Entities\Insumo;
use App\Domain\User\Entities\User;
use App\Domain\Notificacao\Entities\Notificacao;
use PHPUnit\Framework\TestCase;

class EntitiesTest extends TestCase
{
    // ── Peca ──────────────────────────────────────────────────────

    private function makePeca(int $id = 1): Peca
    {
        return new Peca(
            id: $id,
            nome: 'Filtro de Óleo',
            codigo: 'FO-001',
            categoria: 'Filtros',
            precoUnitario: 45.90,
            estoqueAtual: 10,
            estoqueMininmo: 2,
            criadoEm: new \DateTimeImmutable(),
        );
    }

    public function test_peca_getters_retornam_valores_corretos(): void
    {
        $peca = $this->makePeca(1);

        $this->assertEquals(1, $peca->getId());
        $this->assertEquals('Filtro de Óleo', $peca->getNome());
        $this->assertEquals('FO-001', $peca->getCodigo());
        $this->assertEquals('Filtros', $peca->getCategoria());
        $this->assertEquals(45.90, $peca->getPrecoUnitario());
        $this->assertEquals(10, $peca->getEstoqueAtual());
        $this->assertEquals(2, $peca->getEstoqueMininmo());
        $this->assertNotNull($peca->getCriadoEm());
        $this->assertNull($peca->getAtualizadoEm());
    }

    public function test_peca_atualizar_muda_valores(): void
    {
        $peca = $this->makePeca(1);

        $peca->atualizar('Filtro de Ar', 'FA-002', 'Filtros', 35.00, 15, 3);

        $this->assertEquals('Filtro de Ar', $peca->getNome());
        $this->assertEquals('FA-002', $peca->getCodigo());
        $this->assertEquals(35.00, $peca->getPrecoUnitario());
        $this->assertEquals(15, $peca->getEstoqueAtual());
        $this->assertEquals(3, $peca->getEstoqueMininmo());
        $this->assertNotNull($peca->getAtualizadoEm());
    }

    public function test_peca_atualizar_categoria_para_null(): void
    {
        $peca = $this->makePeca(1);

        $peca->atualizar('Filtro', 'FO-001', null, 45.90, 10, 2);

        $this->assertNull($peca->getCategoria());
    }

    public function test_peca_json_serialize_retorna_estrutura_correta(): void
    {
        $peca = $this->makePeca(1);
        $array = $peca->jsonSerialize();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nome', $array);
        $this->assertArrayHasKey('codigo', $array);
        $this->assertArrayHasKey('categoria', $array);
        $this->assertArrayHasKey('preco_unitario', $array);
        $this->assertArrayHasKey('estoque_atual', $array);
        $this->assertArrayHasKey('estoque_minimo', $array);
        $this->assertArrayHasKey('criado_em', $array);
    }

    public function test_peca_sem_id_retorna_null(): void
    {
        $peca = new Peca(
            id: null,
            nome: 'Nova',
            codigo: 'N-001',
            categoria: null,
            precoUnitario: 10.00,
            estoqueAtual: 0,
            estoqueMininmo: 0,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertNull($peca->getId());
    }

    // ── Insumo ────────────────────────────────────────────────────

    private function makeInsumo(int $id = 1): Insumo
    {
        return new Insumo(
            id: $id,
            nome: 'Óleo Motor 5W30',
            unidadeMedida: 'L',
            precoUnitario: 25.90,
            estoqueAtual: 50.0,
            estoqueMininmo: 10.0,
            criadoEm: new \DateTimeImmutable(),
        );
    }

    public function test_insumo_getters_retornam_valores_corretos(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->assertEquals(1, $insumo->getId());
        $this->assertEquals('Óleo Motor 5W30', $insumo->getNome());
        $this->assertEquals('L', $insumo->getUnidadeMedida());
        $this->assertEquals(25.90, $insumo->getPrecoUnitario());
        $this->assertEquals(50.0, $insumo->getEstoqueAtual());
        $this->assertEquals(10.0, $insumo->getEstoqueMininmo());
        $this->assertNotNull($insumo->getCriadoEm());
        $this->assertNull($insumo->getAtualizadoEm());
    }

    public function test_insumo_atualizar_muda_valores(): void
    {
        $insumo = $this->makeInsumo(1);

        $insumo->atualizar('Óleo 10W40', 'L', 28.50, 45.0, 8.0);

        $this->assertEquals('Óleo 10W40', $insumo->getNome());
        $this->assertEquals(28.50, $insumo->getPrecoUnitario());
        $this->assertEquals(45.0, $insumo->getEstoqueAtual());
        $this->assertEquals(8.0, $insumo->getEstoqueMininmo());
        $this->assertNotNull($insumo->getAtualizadoEm());
    }

    public function test_insumo_json_serialize_retorna_estrutura_correta(): void
    {
        $insumo = $this->makeInsumo(1);
        $array = $insumo->jsonSerialize();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nome', $array);
        $this->assertArrayHasKey('unidade_medida', $array);
        $this->assertArrayHasKey('preco_unitario', $array);
        $this->assertArrayHasKey('estoque_atual', $array);
        $this->assertArrayHasKey('estoque_minimo', $array);
        $this->assertArrayHasKey('criado_em', $array);
    }

    // ── User ──────────────────────────────────────────────────────

    public function test_user_getters_retornam_valores_corretos(): void
    {
        $user = new User(
            id: 1,
            name: 'Admin',
            email: 'admin@email.com',
            password: 'hashed',
            role: 'admin',
        );

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('Admin', $user->getName());
        $this->assertEquals('admin@email.com', $user->getEmail());
        $this->assertEquals('hashed', $user->getPassword());
        $this->assertEquals('admin', $user->getRole());
    }

    public function test_user_is_admin_retorna_true_para_admin(): void
    {
        $user = new User(1, 'Admin', 'a@a.com', 'hash', 'admin');
        $this->assertTrue($user->isAdmin());
    }

    public function test_user_is_admin_retorna_false_para_user(): void
    {
        $user = new User(1, 'User', 'u@u.com', 'hash', 'user');
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_admin_retorna_false_para_mecanico(): void
    {
        $user = new User(1, 'Mec', 'm@m.com', 'hash', 'mecanico');
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_role_padrao_e_user(): void
    {
        $user = new User(null, 'Novo', 'novo@email.com', 'hash');
        $this->assertEquals('user', $user->getRole());
    }

    public function test_user_json_serialize_nao_expoe_senha(): void
    {
        $user = new User(1, 'João', 'joao@email.com', 'senha_secreta', 'user');
        $array = $user->jsonSerialize();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('role', $array);
    }

    // ── Notificacao ───────────────────────────────────────────────

    private function makeNotificacao(int $id = 1): Notificacao
    {
        return new Notificacao(
            id: $id,
            ordemServicoId: 10,
            userId: 5,
            tipo: 'OS_RECEBIDA',
            canal: 'sistema',
            status: 'pendente',
            enviadaEm: null,
            lida: false,
            criadaEm: new \DateTimeImmutable(),
        );
    }

    public function test_notificacao_getters_retornam_valores_corretos(): void
    {
        $notificacao = $this->makeNotificacao(1);

        $this->assertEquals(1, $notificacao->getId());
        $this->assertEquals(10, $notificacao->getOrdemServicoId());
        $this->assertEquals(5, $notificacao->getUserId());
        $this->assertEquals('OS_RECEBIDA', $notificacao->getTipo());
        $this->assertEquals('sistema', $notificacao->getCanal());
        $this->assertEquals('pendente', $notificacao->getStatus());
        $this->assertNull($notificacao->getEnviadaEm());
        $this->assertFalse($notificacao->isLida());
        $this->assertNotNull($notificacao->getCriadaEm());
    }

    public function test_notificacao_lida_retorna_true(): void
    {
        $notificacao = new Notificacao(
            id: 1,
            ordemServicoId: 10,
            userId: 5,
            tipo: 'OS_FINALIZADA',
            canal: 'sistema',
            status: 'enviado',
            enviadaEm: new \DateTimeImmutable(),
            lida: true,
            criadaEm: new \DateTimeImmutable(),
        );

        $this->assertTrue($notificacao->isLida());
        $this->assertNotNull($notificacao->getEnviadaEm());
    }

    public function test_notificacao_to_array_retorna_estrutura_correta(): void
    {
        $notificacao = $this->makeNotificacao(1);
        $array = $notificacao->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('ordem_servico_id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('tipo', $array);
        $this->assertArrayHasKey('canal', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('lida', $array);
        $this->assertFalse($array['lida']);
    }

    public function test_notificacao_json_serialize_igual_to_array(): void
    {
        $notificacao = $this->makeNotificacao(1);

        $this->assertEquals($notificacao->toArray(), $notificacao->jsonSerialize());
    }
}
