<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\UserService;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserServiceAdditionalTest extends TestCase
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

    public function test_buscar_por_email_inexistente_lanca_excecao_com_mensagem_correta(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Usuário com email 'naoexiste@email.com' não encontrado.");

        $this->service->buscarPorEmail('naoexiste@email.com');
    }

    public function test_buscar_por_id_inexistente_lanca_excecao_com_id_correto(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Usuário #42 não encontrado.');

        $this->service->buscarPorId(42);
    }

    public function test_atualizar_user_com_password_novo(): void
    {
        $user = $this->makeUser(1);

        $this->repository->method('findById')->willReturn($user);
        $this->repository->method('save')->willReturn($user);

        // atualizar sem password não deve lançar exceção
        $result = $this->service->atualizar(1, [
            'name'  => 'João Atualizado',
            'email' => 'joao@email.com',
        ]);

        $this->assertInstanceOf(User::class, $result);
    }

    public function test_atualizar_user_mantem_dados_nao_informados(): void
    {
        $user = $this->makeUser(1, 'mecanico');

        $this->repository->method('findById')->willReturn($user);
        $this->repository->method('save')->willReturn($user);

        $result = $this->service->atualizar(1, []);

        $this->assertInstanceOf(User::class, $result);
    }

    public function test_remover_user_chama_delete_com_id_correto(): void
    {
        $user = $this->makeUser(5);

        $this->repository->method('findById')->willReturn($user);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(5);

        $this->service->remover(5);
    }

    public function test_user_admin_retorna_role_admin(): void
    {
        $user = $this->makeUser(1, 'admin');

        $this->repository->method('findById')->willReturn($user);

        $result = $this->service->buscarPorId(1);

        $this->assertEquals('admin', $result->getRole());
        $this->assertTrue($result->isAdmin());
    }

    public function test_user_mecanico_nao_e_admin(): void
    {
        $user = $this->makeUser(1, 'mecanico');

        $this->repository->method('findById')->willReturn($user);

        $result = $this->service->buscarPorId(1);

        $this->assertFalse($result->isAdmin());
    }
}
