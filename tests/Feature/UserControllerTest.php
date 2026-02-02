<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserSchoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private MockInterface $mockSchool;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'school-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        Role::create(['name' => 'staff', 'guard_name' => 'web']);

        // Set team ID before creating user with roles
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('school-admin');

        // Create a mock school object  
        $this->mockSchool = Mockery::mock('App\Models\School');
        $this->mockSchool->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Mock the UserSchoolService to return our mock school
        $this->mock(UserSchoolService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getSchool')->andReturn($this->mockSchool);
            $mock->shouldReceive('getSchoolId')->andReturn(1);
            $mock->shouldReceive('hasSchool')->andReturn(true);
        });
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    public function test_can_list_available_roles(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/users/available-roles');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}

