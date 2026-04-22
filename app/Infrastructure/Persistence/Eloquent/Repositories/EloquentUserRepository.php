<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $model = UserModel::query()->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::query()->where('email', $email)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByRole(string $role): array
    {
        return UserModel::query()
            ->where('role', strtolower(trim($role)))
            ->get()
            ->map(fn(UserModel $model) => $this->toEntity($model))
            ->toArray();
    }

    public function save(User $user): User
    {
        $model = $user->getId()
            ? UserModel::query()->findOrFail($user->getId())
            : new UserModel();

        $model->fill([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
        ]);

        $model->save();

        return $this->toEntity($model->refresh());
    }

    public function findAll(): array
    {
        return UserModel::query()
            ->get()
            ->map(fn(UserModel $model) => $this->toEntity($model))
            ->toArray();
    }

    public function delete(int $id): void
    {
        UserModel::query()->findOrFail($id)->delete();
    }

    private function toEntity(UserModel $model): User
    {
        return new User(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            password: $model->password,
            role: $model->role ?? 'user',
        );
    }
}
