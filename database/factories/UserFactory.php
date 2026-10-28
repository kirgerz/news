<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id' => Role::where('name', 'Читатель')->firstOrFail()->id,
            'login' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function journalist(): static
    {
        return $this->state(fn (): array => ['role_id' => Role::where('name', 'Журналист')->firstOrFail()->id]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role_id' => Role::where('name', 'Администратор')->firstOrFail()->id]);
    }
}
