<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserDetailFactory extends Factory
{
    protected $model = UserDetail::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'alamat' => $this->faker->address(),
            'phonenumber' => $this->faker->phoneNumber(),
            'prodi' => $this->faker->numberBetween(1, 10),
            'jurusan' => $this->faker->numberBetween(1, 5),
        ];
    }
}