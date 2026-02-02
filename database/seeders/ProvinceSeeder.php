<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Downloading provinces data from GitHub...');
        
        $response = Http::get('https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/province.json');
        
        if (!$response->successful()) {
            $this->command->error('Failed to download provinces data. Using fallback data...');
            $this->seedFallbackData();
            return;
        }
        
        $provinces = $response->json();
        
        $insertData = [];
        foreach ($provinces as $province) {
            $insertData[] = [
                'id' => $province['id'], // ใช้ id จาก API เพื่อให้ตรงกับ province_id ใน amphures
                'code' => $province['id'], // เก็บ code = id
                'name_th' => $province['name_th'],
                'name_en' => $province['name_en'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('provinces')->insert($insertData);
        
        $this->command->info('Inserted ' . count($insertData) . ' provinces.');
    }
    
    /**
     * Fallback data if download fails
     */
    private function seedFallbackData(): void
    {
        // ข้อมูล 77 จังหวัดของประเทศไทย (ใช้ id ตาม API)
        $provinces = [
            ['id' => 1, 'code' => 1, 'name_th' => 'กรุงเทพมหานคร', 'name_en' => 'Bangkok'],
            ['id' => 2, 'code' => 2, 'name_th' => 'สมุทรปราการ', 'name_en' => 'Samut Prakan'],
            ['id' => 3, 'code' => 3, 'name_th' => 'นนทบุรี', 'name_en' => 'Nonthaburi'],
            ['id' => 4, 'code' => 4, 'name_th' => 'ปทุมธานี', 'name_en' => 'Pathum Thani'],
            ['id' => 5, 'code' => 5, 'name_th' => 'พระนครศรีอยุธยา', 'name_en' => 'Phra Nakhon Si Ayutthaya'],
            ['id' => 6, 'code' => 6, 'name_th' => 'อ่างทอง', 'name_en' => 'Ang Thong'],
            ['id' => 7, 'code' => 7, 'name_th' => 'ลพบุรี', 'name_en' => 'Lop Buri'],
            ['id' => 8, 'code' => 8, 'name_th' => 'สิงห์บุรี', 'name_en' => 'Sing Buri'],
            ['id' => 9, 'code' => 9, 'name_th' => 'ชัยนาท', 'name_en' => 'Chai Nat'],
            ['id' => 10, 'code' => 10, 'name_th' => 'สระบุรี', 'name_en' => 'Saraburi'],
            // ... เพิ่มจังหวัดอื่นๆ ตามต้องการ
        ];

        DB::table('provinces')->insert($provinces);
        $this->command->info('Inserted fallback provinces data (10 provinces).');
    }
}
