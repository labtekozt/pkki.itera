<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionType;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        $submissionType = SubmissionType::factory()->create();
        $stage = WorkflowStage::factory()->create([
            'submission_type_id' => $submissionType->id,
            'order' => 1,
        ]);

        $statuses = ['draft', 'submitted', 'in_review', 'revision_needed', 'approved', 'rejected', 'completed'];

        return [
            'id' => Str::uuid(),
            'submission_type_id' => $submissionType->id,
            'current_stage_id' => $stage->id,
            'title' => ucwords($this->faker->words(4, true)),
            'status' => $this->faker->randomElement($statuses),
            'certificate' => null,
            'reviewer_notes' => null,
            'user_id' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
            'current_stage_id' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'submitted',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'certificate' => strtoupper($this->faker->bothify('??#####')),
        ]);
    }

    public function forType(SubmissionType $type): static
    {
        $stage = WorkflowStage::where('submission_type_id', $type->id)
            ->orderBy('order')
            ->first();

        if (!$stage) {
            $stage = WorkflowStage::factory()->create([
                'submission_type_id' => $type->id,
                'order' => 1,
            ]);
        }

        return $this->state(fn(array $attributes) => [
            'submission_type_id' => $type->id,
            'current_stage_id' => $stage->id,
        ]);
    }
}
