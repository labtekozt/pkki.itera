<?php

namespace Database\Factories;

use App\Models\SubmissionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubmissionTypeFactory extends Factory
{
    protected $model = SubmissionType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        
        return [
            'id' => Str::uuid(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    public function paten(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Paten',
            'slug' => 'paten',
            'description' => 'Perlindungan invensi di bidang teknologi',
        ]);
    }

    public function brand(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Merek',
            'slug' => 'brand',
            'description' => 'Perlindungan tanda yang dapat ditampilkan secara grafis',
        ]);
    }

    public function haki(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Hak Cipta',
            'slug' => 'haki',
            'description' => 'Perlindungan hasil karya intelektual dalam bidang ilmu pengetahuan, seni, dan sastra',
        ]);
    }

    public function industrialDesign(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Desain Industri',
            'slug' => 'industrial_design',
            'description' => 'Perlindungan kreasi bentuk, konfigurasi, komposisi garis atau warna, atau kombinasinya',
        ]);
    }
}