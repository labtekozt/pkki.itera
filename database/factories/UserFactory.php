<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'fullname' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withSso(string $provider = 'google'): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
            'provider_id' => $this->faker->uuid,
            'avatar' => $this->faker->imageUrl(200, 200, 'people'),
            'password' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email' => 'admin@example.com',
            ];
        });
    }
}