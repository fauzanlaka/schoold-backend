<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserSchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function __construct(
        private UserSchoolService $userSchoolService,
        private PermissionRegistrar $permissionRegistrar
    ) {}

    /**
     * Display a listing of users for the current school.
     */
    public function index(Request $request): JsonResponse
    {
        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        // Set team ID for proper role scoping
        $this->permissionRegistrar->setPermissionsTeamId($school->id);

        $query = $school->users()->with('roles')->withPivot('is_active');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role') && $request->role) {
            $query->role($request->role);
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'position' => $request->position,
        ]);

        // Attach user to school
        $school->users()->attach($user->id, ['is_active' => true]);

        // Set team ID for role assignment (school-scoped permissions)
        $this->permissionRegistrar->setPermissionsTeamId($school->id);

        // Assign role with school context
        $user->assignRole($request->role);

        // Send email verification
        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มผู้ใช้งานสำเร็จ',
            'data' => $user->load('roles'),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(int $userId): JsonResponse
    {
        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        // Set team ID for proper role scoping
        $this->permissionRegistrar->setPermissionsTeamId($school->id);

        $user = $school->users()->with('roles', 'permissions')->withPivot('is_active')->find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งาน',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, int $userId): JsonResponse
    {
        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $user = $school->users()->find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งาน',
            ], 404);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'position' => $request->position,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Update role if provided
        if ($request->filled('role')) {
            // Set team ID for role assignment (school-scoped permissions)
            $this->permissionRegistrar->setPermissionsTeamId($school->id);
            $user->syncRoles([$request->role]);
        }

        // Update pivot is_active if provided
        if ($request->has('is_active')) {
            $school->users()->updateExistingPivot($userId, [
                'is_active' => $request->is_active,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขผู้ใช้งานสำเร็จ',
            'data' => $user->fresh()->load('roles'),
        ]);
    }

    /**
     * Remove the specified user from the school.
     */
    public function destroy(int $userId): JsonResponse
    {
        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $user = $school->users()->find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งาน',
            ], 404);
        }

        // Detach from school instead of deleting user
        $school->users()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'ลบผู้ใช้งานออกจากโรงเรียนสำเร็จ',
        ]);
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ], [
            'role.required' => 'กรุณาระบุ Role',
            'role.exists' => 'Role ที่เลือกไม่ถูกต้อง',
        ]);

        $school = $this->userSchoolService->getSchool();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $user = $school->users()->find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งาน',
            ], 404);
        }

        // Set team ID for role assignment (school-scoped permissions)
        $this->permissionRegistrar->setPermissionsTeamId($school->id);

        $user->syncRoles([$request->role]);

        return response()->json([
            'success' => true,
            'message' => 'กำหนด Role สำเร็จ',
            'data' => $user->load('roles'),
        ]);
    }

    /**
     * Get available roles for the dropdown.
     * Returns system roles and school-specific roles.
     */
    public function availableRoles(): JsonResponse
    {
        $schoolId = $this->userSchoolService->getSchoolId();

        $roles = Role::query()
            ->select('id', 'name', 'school_id')
            ->where('name', '!=', 'super-admin')
            ->where(function ($query) use ($schoolId) {
                // System roles (school_id = null) OR school-specific roles
                $query->whereNull('school_id')
                    ->orWhere('school_id', $schoolId);
            })
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_system' => $role->school_id === null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}
