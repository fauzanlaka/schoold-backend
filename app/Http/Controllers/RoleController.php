<?php

namespace App\Http\Controllers;

use App\Services\UserSchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private UserSchoolService $userSchoolService) {}

    /**
     * Display a listing of roles.
     * Shows both system roles (school_id = null) and school-specific roles.
     */
    public function index(): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        // Get system roles (school_id = null) and school-specific roles
        $roles = Role::query()
            ->where(function ($query) use ($schoolId) {
                $query->whereNull('school_id')
                    ->orWhere('school_id', $schoolId);
            })
            ->with('permissions:id,name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'school_id' => $role->school_id,
                    'is_system' => $role->school_id === null,
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role with school_id.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'กรุณากรอกชื่อ Role',
            'name.unique' => 'ชื่อ Role นี้มีอยู่แล้ว',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'school_id' => $schoolId, // Save school_id for school-specific role
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้าง Role สำเร็จ',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'school_id' => $role->school_id,
                'is_system' => false,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(int $id): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        $role = Role::with('permissions:id,name')
            ->where(function ($query) use ($schoolId) {
                $query->whereNull('school_id')
                    ->orWhere('school_id', $schoolId);
            })
            ->find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'school_id' => $role->school_id,
                'is_system' => $role->school_id === null,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        $role = Role::where(function ($query) use ($schoolId) {
            $query->whereNull('school_id')
                ->orWhere('school_id', $schoolId);
        })->find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        // Prevent editing system roles
        if ($role->school_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถแก้ไข Role ระบบได้',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'กรุณากรอกชื่อ Role',
            'name.unique' => 'ชื่อ Role นี้มีอยู่แล้ว',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'แก้ไข Role สำเร็จ',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'school_id' => $role->school_id,
                'is_system' => $role->school_id === null,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        $role = Role::where(function ($query) use ($schoolId) {
            $query->whereNull('school_id')
                ->orWhere('school_id', $schoolId);
        })->find($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Role',
            ], 404);
        }

        // Prevent deleting system roles
        if ($role->school_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบ Role ระบบได้',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ Role สำเร็จ',
        ]);
    }
}
