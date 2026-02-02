<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SubdistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * ข้อมูลตำบลของประเทศไทย (ดาวน์โหลดจาก GitHub)
     */
    public function run(): void
    {
        $this->command->info('Downloading subdistricts data from GitHub...');
        
        $response = Http::get('https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/sub_district.json');
        
        if (!$response->successful()) {
            $this->command->error('Failed to download subdistricts data. Using fallback data...');
            $this->seedFallbackData();
            return;
        }
        
        $subdistricts = $response->json();
        
        $insertData = [];
        foreach ($subdistricts as $subdistrict) {
            $insertData[] = [
                'id' => $subdistrict['id'],
                'code' => $subdistrict['id'],
                'name_th' => $subdistrict['name_th'],
                'name_en' => $subdistrict['name_en'] ?? null,
                'postal_code' => isset($subdistrict['zip_code']) ? (string) $subdistrict['zip_code'] : null,
                'amphure_id' => $subdistrict['district_id'], // API ใช้ชื่อ district_id
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Insert in chunks to avoid memory issues
        $this->command->info('Inserting ' . count($insertData) . ' subdistricts in chunks...');
        
        foreach (array_chunk($insertData, 500) as $index => $chunk) {
            DB::table('subdistricts')->insert($chunk);
            $this->command->info('Inserted chunk ' . ($index + 1));
        }
        
        $this->command->info('Inserted ' . count($insertData) . ' subdistricts.');
    }
    
    /**
     * Fallback data for Bangkok subdistricts if download fails
     */
    private function seedFallbackData(): void
    {
        // Sample Phra Nakhon subdistricts as fallback
        $subdistricts = [
            ['id' => 100101, 'code' => 100101, 'name_th' => 'พระบรมมหาราชวัง', 'name_en' => 'Phra Borom Maha Ratchawang', 'postal_code' => '10200', 'amphure_id' => 1001],
            ['id' => 100102, 'code' => 100102, 'name_th' => 'วังบูรพาภิรมย์', 'name_en' => 'Wang Burapha Phirom', 'postal_code' => '10200', 'amphure_id' => 1001],
            ['id' => 100103, 'code' => 100103, 'name_th' => 'วัดราชบพิธ', 'name_en' => 'Wat Ratchabophit', 'postal_code' => '10200', 'amphure_id' => 1001],
            ['id' => 100104, 'code' => 100104, 'name_th' => 'สำราญราษฎร์', 'name_en' => 'Samran Rat', 'postal_code' => '10200', 'amphure_id' => 1001],
            ['id' => 100105, 'code' => 100105, 'name_th' => 'ศาลเจ้าพ่อเสือ', 'name_en' => 'San Chao Pho Suea', 'postal_code' => '10200', 'amphure_id' => 1001],
        ];
        
        DB::table('subdistricts')->insert($subdistricts);
        $this->command->info('Inserted fallback subdistricts data (5 sub-districts).');
    }
}
