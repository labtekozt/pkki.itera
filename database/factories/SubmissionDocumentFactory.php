<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubmissionDocumentFactory extends Factory
{
    protected $model = SubmissionDocument::class;

    public function definition(): array
    {
        $statuses = ['pending', 'approved', 'rejected', 'revision_needed'];

        return [
            'id' => Str::uuid(),
            'submission_id' => Submission::factory(),
            'document_id' => Document::factory(),
            'requirement_id' => DocumentRequirement::factory(),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function rejected(string $reason = null): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'notes' => $reason ?? $this->faker->sentence(),
        ]);
    }
}
