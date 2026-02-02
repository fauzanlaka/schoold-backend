<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // User management
            ['name' => 'users.view', 'description' => 'ดูข้อมูลผู้ใช้งาน'],
            ['name' => 'users.create', 'description' => 'สร้างผู้ใช้งาน'],
            ['name' => 'users.edit', 'description' => 'แก้ไขผู้ใช้งาน'],
            ['name' => 'users.delete', 'description' => 'ลบผู้ใช้งาน'],
            
            // Role management
            ['name' => 'roles.view', 'description' => 'ดูข้อมูลสิทธิ์การใช้งาน'],
            ['name' => 'roles.create', 'description' => 'สร้างสิทธิ์การใช้งาน'],
            ['name' => 'roles.edit', 'description' => 'แก้ไขสิทธิ์การใช้งาน'],
            ['name' => 'roles.delete', 'description' => 'ลบสิทธิ์การใช้งาน'],
            
            // School management
            ['name' => 'school.view', 'description' => 'ดูข้อมูลโรงเรียน'],
            ['name' => 'school.edit', 'description' => 'แก้ไขข้อมูลโรงเรียน'],
            
            // Asset management
            ['name' => 'assets.view', 'description' => 'ดูรายการครุภัณฑ์'],
            ['name' => 'assets.create', 'description' => 'เพิ่มครุภัณฑ์'],
            ['name' => 'assets.edit', 'description' => 'แก้ไขครุภัณฑ์'],
            ['name' => 'assets.delete', 'description' => 'ลบครุภัณฑ์'],
            ['name' => 'assets.report', 'description' => 'ออกรายงานครุภัณฑ์'],
            
            // Asset category management
            ['name' => 'asset-categories.view', 'description' => 'ดูหมวดหมู่ครุภัณฑ์'],
            ['name' => 'asset-categories.create', 'description' => 'สร้างหมวดหมู่ครุภัณฑ์'],
            ['name' => 'asset-categories.edit', 'description' => 'แก้ไขหมวดหมู่ครุภัณฑ์'],
            ['name' => 'asset-categories.delete', 'description' => 'ลบหมวดหมู่ครุภัณฑ์'],
        ];

        // Create permissions
        // Create permissions
        // Create permissions
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description']]
            );
        }

        // Create roles and assign permissions
        
        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // School Admin - can manage users and school settings
        $schoolAdmin = Role::firstOrCreate(['name' => 'school-admin', 'guard_name' => 'web']);
        $schoolAdmin->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view',
            'school.view', 'school.edit',
            'assets.view', 'assets.create', 'assets.edit', 'assets.delete', 'assets.report',
            'asset-categories.view', 'asset-categories.create', 'asset-categories.edit', 'asset-categories.delete',
        ]);

        // Teacher - limited access
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->givePermissionTo([
            'school.view',
            'assets.view',
            'asset-categories.view',
        ]);

        // Staff - basic access
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->givePermissionTo([
            'school.view',
            'assets.view', 'assets.create', 'assets.edit',
            'asset-categories.view',
        ]);
    }
}
