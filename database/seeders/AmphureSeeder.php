<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AmphureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ข้อมูลอำเภอของประเทศไทย (ดาวน์โหลดจาก GitHub)
     */
    public function run(): void
    {
        $this->command->info('Downloading amphures (districts) data from GitHub...');
        
        $response = Http::get('https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/district.json');
        
        if (!$response->successful()) {
            $this->command->error('Failed to download amphures data. Using fallback data...');
            $this->seedFallbackData();
            return;
        }
        
        $amphures = $response->json();
        
        $insertData = [];
        foreach ($amphures as $amphure) {
            $insertData[] = [
                'id' => $amphure['id'], // ใช้ id จาก API เพื่อให้ตรงกับ district_id ใน subdistricts
                'code' => $amphure['id'],
                'name_th' => $amphure['name_th'],
                'name_en' => $amphure['name_en'] ?? null,
                'province_id' => $amphure['province_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Insert in chunks to avoid memory issues
        foreach (array_chunk($insertData, 100) as $chunk) {
            DB::table('amphures')->insert($chunk);
        }
        
        $this->command->info('Inserted ' . count($insertData) . ' amphures.');
    }
    
    /**
     * Fallback data for Bangkok amphures if download fails
     */
    private function seedFallbackData(): void
    {
        // Sample Bangkok districts as fallback
        $amphures = [
            ['id' => 1001, 'code' => 1001, 'name_th' => 'เขตพระนคร', 'name_en' => 'Khet Phra Nakhon', 'province_id' => 1],
            ['id' => 1002, 'code' => 1002, 'name_th' => 'เขตดุสิต', 'name_en' => 'Khet Dusit', 'province_id' => 1],
            ['id' => 1003, 'code' => 1003, 'name_th' => 'เขตหนองจอก', 'name_en' => 'Khet Nong Chok', 'province_id' => 1],
            ['id' => 1004, 'code' => 1004, 'name_th' => 'เขตบางรัก', 'name_en' => 'Khet Bang Rak', 'province_id' => 1],
            ['id' => 1005, 'code' => 1005, 'name_th' => 'เขตบางเขน', 'name_en' => 'Khet Bang Khen', 'province_id' => 1],
        ];
        
        DB::table('amphures')->insert($amphures);
        $this->command->info('Inserted fallback amphures data (5 districts).');
    }
}
