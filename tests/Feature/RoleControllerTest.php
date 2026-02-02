<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserSchoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private int $schoolId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'users.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web']);

        // Set team ID before creating user with roles
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->schoolId);

        // Create admin user
        $this->adminUser = User::factory()->create();

        // Mock the UserSchoolService
        $this->mock(UserSchoolService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getSchool')->andReturn((object) ['id' => $this->schoolId]);
            $mock->shouldReceive('getSchoolId')->andReturn($this->schoolId);
            $mock->shouldReceive('hasSchool')->andReturn(true);
        });
    }

    public function test_can_list_roles(): void
    {
        // System role (school_id = null)
        Role::create(['name' => 'system-role', 'guard_name' => 'web', 'school_id' => null]);
        // School-specific role
        Role::create(['name' => 'school-role', 'guard_name' => 'web', 'school_id' => $this->schoolId]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Should see both system and school-specific roles
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_can_create_role_with_school_id(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/roles', [
                'name' => 'new-role',
                'permissions' => ['users.view'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'สร้าง Role สำเร็จ',
            ]);

        // Role should have school_id
        $this->assertDatabaseHas('roles', [
            'name' => 'new-role',
            'school_id' => $this->schoolId,
        ]);
    }

    public function test_can_update_school_role(): void
    {
        // Create school-specific role
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web', 'school_id' => $this->schoolId]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/roles/{$role->id}", [
                'name' => 'updated-role',
                'permissions' => ['users.view', 'users.create'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('roles', ['name' => 'updated-role']);
    }

    public function test_cannot_update_system_role(): void
    {
        // Create system role (school_id = null)
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web', 'school_id' => null]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/roles/{$role->id}", [
                'name' => 'hacked-admin',
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'ไม่สามารถแก้ไข Role ระบบได้']);
    }

    public function test_cannot_delete_system_role(): void
    {
        // System role (school_id = null)
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web', 'school_id' => null]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'ไม่สามารถลบ Role ระบบได้']);
    }

    public function test_can_delete_school_role(): void
    {
        // School-specific role
        $role = Role::create(['name' => 'custom-role', 'guard_name' => 'web', 'school_id' => $this->schoolId]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('roles', ['name' => 'custom-role']);
    }
}
