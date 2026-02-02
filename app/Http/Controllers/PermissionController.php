<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of all permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::query()
            ->select('id', 'name', 'description', 'guard_name')
            ->get()
            ->groupBy(function ($permission) {
                // Group by the first part of permission name (e.g., users.view -> users)
                return explode('.', $permission->name)[0];
            });

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
