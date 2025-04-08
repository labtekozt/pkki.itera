<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Submission;
use App\Models\TrackingHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TrackingHistoryFactory extends Factory
{
    protected $model = TrackingHistory::class;

    public function definition(): array
    {
        $submission = Submission::factory()->create();
        $statuses = ['started', 'in_progress', 'approved', 'rejected', 'revision_needed', 'completed'];

        return [
            'id' => Str::uuid(),
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'status' => $this->faker->randomElement($statuses),
            'comment' => $this->faker->sentence(),
            'document_id' => null,
            'processed_by' => null,
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function withAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'processed_by' => User::factory()->admin(),
        ]);
    }

    public function withProcessor(): static
    {
        return $this->state(fn(array $attributes) => [
            'processed_by' => User::factory(),
        ]);
    }

    public function withAttachment(): static
    {
        return $this->state(fn(array $attributes) => [
            'document_id' => Document::factory(),
        ]);
    }

    public function stageStarted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'started',
            'comment' => 'Stage has been initiated',
        ]);
    }

    public function needsRevision(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'revision_needed',
            'comment' => $this->faker->paragraph(),
        ]);
    }
}
