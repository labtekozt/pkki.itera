<?php

namespace Database\Factories;

use App\Models\DocumentRequirement;
use App\Models\SubmissionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentRequirementFactory extends Factory
{
    protected $model = DocumentRequirement::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'id' => Str::uuid(),
            'submission_type_id' => SubmissionType::factory(),
            'code' => Str::slug($name),
            'name' => ucwords($name),
            'description' => $this->faker->sentence(),
            'required' => $this->faker->boolean(80), // 80% chance of being required
            'order' => $this->faker->numberBetween(1, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
