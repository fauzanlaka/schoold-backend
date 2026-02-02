<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GradeLevel;

class GradeLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gradeLevels = [
            ['name' => 'อนุบาล', 'code' => 'KINDER'],
            ['name' => 'ประถมศึกษา', 'code' => 'PRIMARY'],
            ['name' => 'มัธยมศึกษาตอนต้น', 'code' => 'SECONDARY_LOW'],
            ['name' => 'มัธยมศึกษาตอนปลาย', 'code' => 'SECONDARY_HIGH'],
        ];

        foreach ($gradeLevels as $level) {
            GradeLevel::updateOrCreate(
                ['code' => $level['code']],
                ['name' => $level['name']]
            );
        }
    }
}
