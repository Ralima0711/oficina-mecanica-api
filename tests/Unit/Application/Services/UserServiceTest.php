<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\UserService;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;
    private UserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new UserService($this->repository);
    }

    private function makeUser(int $id = 1, string $role = 'user'): User
    {
        return new User(
            id: $id,
            name: 'João Silva',
            email: 'joao@email.com',
            password: '$2y$10$hashedpassword',
            role: $role,
        );
    }

    // ── listarTodos ───────────────────────────────────────────────

    public function test_listar_todos_retorna_array(): void
    {
        $users = [$this->makeUser(1), $this->makeUser(2)];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($users);

        $result = $this->service->listarTodos();

        $this->assertCount(2, $result);
    }

    public function test_listar_todos_retorna_array_vazio(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $result = $this->service->listarTodos();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── buscarPorId ───────────────────────────────────────────────

    public function test_buscar_por_id_retorna_user_existente(): void
    {
        $user = $this->makeUser(1);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($user);

        $result = $this->service->buscarPorId(1);

        $this->assertSame($user, $result);
    }

    public function test_buscar_por_id_lanca_excecao_quando_nao_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Usuário #99 não encontrado.');

        $this->service->buscarPorId(99);
    }

    // ── buscarPorEmail ────────────────────────────────────────────

    public function test_buscar_por_email_retorna_user_existente(): void
    {
        $user = $this->makeUser(1);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('joao@email.com')
            ->willReturn($user);

        $result = $this->service->buscarPorEmail('joao@email.com');

        $this->assertSame($user, $result);
    }

    public function test_buscar_por_email_lanca_excecao_quando_nao_encontrado(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Usuário com email 'inexistente@email.com' não encontrado.");

        $this->service->buscarPorEmail('inexistente@email.com');
    }

    // ── criar ─────────────────────────────────────────────────────

    public function test_criar_user_email_ja_existente_lanca_excecao(): void
    {
        $user = $this->makeUser(1);

        $this->repository->method('findByEmail')->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email já cadastrado no sistema.');

        $this->service->criar([
            'name'     => 'Outro Nome',
            'email'    => 'joao@email.com',
            'password' => 'outrasenha',
        ]);
    }

    public function test_user_entity_pode_ser_criada_com_dados_validos(): void
    {
        $user = $this->makeUser(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('joao@email.com', $user->getEmail());
        $this->assertEquals('user', $user->getRole());
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_user_existente_com_sucesso(): void
    {
        $user = $this->makeUser(1);

        $this->repository->method('findById')->willReturn($user);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($user);

        $result = $this->service->atualizar(1, [
            'name'  => 'João Atualizado',
            'email' => 'novo@email.com',
        ]);

        $this->assertInstanceOf(User::class, $result);
    }

    public function test_atualizar_user_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->atualizar(99, ['name' => 'X']);
    }

    // ── remover ───────────────────────────────────────────────────

    public function test_remover_user_existente_com_sucesso(): void
    {
        $user = $this->makeUser(1);

        $this->repository->method('findById')->willReturn($user);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->remover(1);
    }

    public function test_remover_user_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->remover(99);
    }
}
