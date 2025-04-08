<?php

namespace Database\Seeders;

use App\Models\SubmissionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubmissionTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Create submission types
        $types = [
            [
                'id' => Str::uuid(),
                'name' => 'Paten',
                'slug' => 'paten',
                'description' => 'Perlindungan invensi di bidang teknologi',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Merek',
                'slug' => 'brand',
                'description' => 'Perlindungan tanda yang dapat ditampilkan secara grafis',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Hak Cipta',
                'slug' => 'haki',
                'description' => 'Perlindungan hasil karya intelektual dalam bidang ilmu pengetahuan, seni, dan sastra',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Desain Industri',
                'slug' => 'industrial_design',
                'description' => 'Perlindungan kreasi bentuk, konfigurasi, komposisi garis atau warna, atau kombinasinya',
            ],
        ];
        
        foreach ($types as $type) {
            SubmissionType::firstOrCreate(
                ['slug' => $type['slug']],
                array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}