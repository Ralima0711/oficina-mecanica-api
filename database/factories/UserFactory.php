<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Infrastructure\Persistence\Eloquent\Models\UserModel>
 */
class UserFactory extends Factory
{
    protected $model = UserModel::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role'     => 'atendente',
        ];
    }
}
