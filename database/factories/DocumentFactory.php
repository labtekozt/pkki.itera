<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $fileTypes = ['pdf', 'docx', 'jpg', 'png'];
        $fileType = $this->faker->randomElement($fileTypes);
        $fileSize = $this->faker->numberBetween(10000, 5000000);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        return [
            'id' => Str::uuid(),
            'uri' => 'documents/' . $this->faker->uuid . '.' . $fileType,
            'title' => ucwords($this->faker->words(3, true)),
            'mimetype' => $mimeTypes[$fileType],
            'size' => $fileSize,
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn(array $attributes) => [
            'uri' => 'documents/' . Str::uuid() . '.pdf',
            'mimetype' => 'application/pdf',
        ]);
    }

    public function image(): static
    {
        $imageType = $this->faker->randomElement(['jpg', 'png']);
        $mimeType = $imageType === 'jpg' ? 'image/jpeg' : 'image/png';

        return $this->state(fn(array $attributes) => [
            'uri' => 'documents/' . Str::uuid() . '.' . $imageType,
            'mimetype' => $mimeType,
        ]);
    }
}
