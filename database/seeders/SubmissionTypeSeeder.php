<?php

namespace Database\Seeders;

use App\Models\SubmissionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubmissionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating submission types...');
        
        // Create submission types
        $types = [
            [
                'name' => 'Paten',
                'slug' => 'paten',
                'description' => 'Perlindungan invensi di bidang teknologi',
            ],
            [
                'name' => 'Merek',
                'slug' => 'brand',
                'description' => 'Perlindungan tanda yang dapat ditampilkan secara grafis',
            ],
            [
                'name' => 'Hak Cipta',
                'slug' => 'haki',
                'description' => 'Perlindungan hasil karya intelektual dalam bidang ilmu pengetahuan, seni, dan sastra',
            ],
            [
                'name' => 'Desain Industri',
                'slug' => 'industrial_design',
                'description' => 'Perlindungan kreasi bentuk, konfigurasi, komposisi garis atau warna, atau kombinasinya',
            ],
        ];
        
        foreach ($types as $type) {
            $submissionType = SubmissionType::firstOrCreate(
                ['slug' => $type['slug']],
                [
                    'id' => Str::uuid(),
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            if ($submissionType->wasRecentlyCreated) {
                $this->command->info("✅ Created submission type: {$type['name']}");
            } else {
                $this->command->info("ℹ️ Submission type already exists: {$type['name']}");
            }
        }
        
        $this->command->info('Submission types seeding completed.');
    }
}