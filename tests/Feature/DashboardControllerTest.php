<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_dashboard_stats()
    {
        // Create location dependencies
        $province = \App\Models\Province::create(['name_th' => 'P', 'code' => 1]);
        $amphure = \App\Models\Amphure::create(['name_th' => 'A', 'code' => 1, 'province_id' => $province->id]);
        $subdistrict = \App\Models\Subdistrict::create(['name_th' => 'S', 'code' => 1, 'amphure_id' => $amphure->id]);
        
        $user = User::factory()->create();
        
        $school = \App\Models\School::factory()->create([
            'province_id' => $province->id,
            'amphure_id' => $amphure->id,
            'district_id' => $subdistrict->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        $user->schools()->attach($school);
        
        // Assign a role in this school scope
        setPermissionsTeamId($school->id);
        $role = \Spatie\Permission\Models\Role::create(['name' => 'Test Role', 'guard_name' => 'web', 'school_id' => $school->id]);
        $user->assignRole($role);
        
        $response = $this->actingAs($user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_stats' => [
                        'last_login',
                        'last_login_formatted',
                    ],
                    'users' => [
                        'total',
                        'new_this_month',
                        'roles_count',
                    ],
                    'assets' => [
                        'total',
                        'total_value',
                        'in_repair',
                        'distribution',
                    ],
                    'recent_assets',
                ]
            ]);
    }
}
