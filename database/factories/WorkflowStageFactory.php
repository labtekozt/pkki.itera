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
        $name = $this->faker->unique()->words(2, true);

        return [
            'id' => Str::uuid(),
            'submission_type_id' => SubmissionType::factory(),
            'code' => Str::slug($name),
            'name' => ucwords($name),
            'order' => $this->faker->numberBetween(1, 5),
            'description' => $this->faker->sentence(),
            'required_documents' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withRequiredDocuments(array $documentCodes): static
    {
        return $this->state(fn(array $attributes) => [
            'required_documents' => json_encode($documentCodes),
        ]);
    }
}
