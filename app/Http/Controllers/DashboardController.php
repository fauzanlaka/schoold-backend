<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        // Get the school context (first school for now, similar to AuthController)
        $school = $user->schools()->first();
        $schoolId = $school?->id;

        // 1. School Info
        // $school is already fetched above

        // 2. User Stats
        $userQuery = User::query();
        
        // Scope to school if exists
        if ($schoolId) {
            $userQuery->whereHas('schools', function($q) use ($schoolId) {
                $q->where('school_user.school_id', $schoolId);
            });
        }

        $usersCount = $userQuery->count();
        
        // Count distinct roles assigned to these users in this school context
        $rolesCount = \DB::table('model_has_roles')
            ->whereIn('model_id', (clone $userQuery)->select('users.id'))
            ->where('model_type', User::class)
            ->when($schoolId, function($q) use ($schoolId) {
                // Configured 'team_foreign_key' is 'school_id'
                $q->where('school_id', $schoolId);
            })
            ->distinct('role_id')
            ->count('role_id');
        
        // Clone query for new users to maintain school scope
        $newUsersThisMonth = (clone $userQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // 3. Asset Stats (Scoped to school if school exists, else global or empty)
        $assetQuery = Asset::query();
        if ($schoolId) {
            $assetQuery->where('school_id', $schoolId);
        }

        $assetsCount = $assetQuery->count();
        $totalAssetValue = $assetQuery->sum(\DB::raw('unit_price * quantity'));
        
        $assetsInRepair = (clone $assetQuery)
            ->where('status', Asset::STATUS_REPAIRING)
            ->count();

        $recentAssets = (clone $assetQuery)
            ->with(['category'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->asset_name,
                    'code' => $asset->asset_code,
                    'category' => $asset->category?->name ?? '-',
                    'price' => $asset->total_price,
                    'status' => $asset->status_label,
                    'date' => $asset->created_at->format('d M Y'),
                ];
            });

        // Asset Status Distribution for charts
        $assetStatusDistribution = (clone $assetQuery)
            ->select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                $statusLabels = [
                    Asset::STATUS_ACTIVE => 'active',
                    Asset::STATUS_INACTIVE => 'inactive',
                    Asset::STATUS_DISPOSED => 'disposed',
                    Asset::STATUS_REPAIRING => 'repairing',
                    Asset::STATUS_UNKNOWN => 'unknown',
                ];
                return [$statusLabels[$item->status] ?? 'other' => $item->count];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'user_stats' => [
                    'last_login' => $user->last_login_at?->diffForHumans() ?? 'First login',
                    'last_login_formatted' => $user->last_login_at?->format('d M Y H:i') ?? '-',
                ],
                'users' => [
                    'total' => $usersCount,
                    'new_this_month' => $newUsersThisMonth,
                    'roles_count' => $rolesCount,
                ],
                'assets' => [
                    'total' => $assetsCount,
                    'total_value' => $totalAssetValue,
                    'in_repair' => $assetsInRepair,
                    'distribution' => $assetStatusDistribution,
                ],
                'recent_assets' => $recentAssets,
            ]
        ]);
    }
}
