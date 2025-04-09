<?php

namespace Database\Factories;

use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowStageFactory extends Factory
{
    protected $model = WorkflowStage::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'submission_type_id' => SubmissionType::factory(),
            'code' => $this->faker->slug(),
            'name' => $this->faker->name(),
            'order' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->sentence(),
        ];
    }
}
