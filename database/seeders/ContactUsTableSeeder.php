<?php

namespace Database\Seeders;

use App\Models\ContactUs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContactUsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define possible values for employees field
        $employeeOptions = [
            '1-10',
            '11-50',
            '51-200',
            '201-500',
            '501-1000',
            '1001-5000',
            '5000+'
        ];

        // Define possible values for status
        $statusOptions = ['new', 'read', 'pending', 'responded'];

        // Create 50 contact requests
        for ($i = 0; $i < 50; $i++) {
            $status = fake()->randomElement($statusOptions);

            $contactUs = ContactUs::create([
                'id' => Str::ulid(),
                'firstname' => fake()->firstName(),
                'lastname' => fake()->lastName(),
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'company' => fake()->company(),
                'employees' => fake()->randomElement($employeeOptions),
                'title' => fake()->sentence(rand(4, 8)),
                'message' => fake()->paragraphs(rand(2, 5), true),
                'status' => $status,
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);

            // If the status is 'responded', add reply data
            if ($status === 'responded') {
                $contactUs->update([
                    'reply_title' => 'RE: ' . $contactUs->title,
                    'reply_message' => fake()->paragraphs(rand(1, 3), true),
                ]);
            }
        }
    }
}
