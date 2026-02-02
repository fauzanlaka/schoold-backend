<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// redirect to login when not authenticated
Route::get('/', function () {
    return redirect()->route('login');
})->name('login');

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/email/resend-verification', [AuthController::class, 'resendVerification']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'index']);

    // School routes
    // Route::middleware('can:school.view')->group(function () {
        Route::get('/school', [SchoolController::class, 'show']);
        Route::get('/school/check', [SchoolController::class, 'checkRegistration']);
    // });
    
    // Route::middleware('can:school.edit')->group(function () {
        Route::post('/school', [SchoolController::class, 'store']);
        Route::put('/school', [SchoolController::class, 'update']);
    // });

    // Location routes
    Route::get('/locations/provinces', [LocationController::class, 'getProvinces']);
    Route::get('/locations/amphures/{province_id}', [LocationController::class, 'getAmphures']);
    Route::get('/locations/subdistricts/{amphure_id}', [LocationController::class, 'getSubdistricts']);

    // Grade levels
    Route::get('/grade-levels', [GradeLevelController::class, 'index']);

    // ============ User Management Routes ============
    Route::prefix('users')->group(function () {
        Route::middleware('can:users.view')->group(function () {
             Route::get('/', [UserController::class, 'index']);
             Route::get('/available-roles', [UserController::class, 'availableRoles']);
             Route::get('/{user}', [UserController::class, 'show']);
        });
        
        Route::post('/', [UserController::class, 'store'])->middleware('can:users.create');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('can:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete');
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->middleware('can:users.edit');
    });

    // ============ Role Management Routes ============
    Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('can:roles.create');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('can:roles.view');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.edit');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete');

    // ============ Permission Routes ============
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('can:roles.view');

    // ============ Asset Management Routes ============
    
    // Asset Categories
    Route::middleware('can:asset-categories.view')->group(function () {
        Route::get('/asset-categories', [AssetCategoryController::class, 'index']);
        Route::get('/asset-categories/list-active', [AssetCategoryController::class, 'listActive']);
        Route::get('/asset-categories/download-template', [AssetCategoryController::class, 'downloadTemplate']);
        Route::get('/asset-categories/{assetCategory}', [AssetCategoryController::class, 'show']);
    });
    
    Route::post('/asset-categories', [AssetCategoryController::class, 'store'])->middleware('can:asset-categories.create');
    Route::post('/asset-categories/import', [AssetCategoryController::class, 'import'])->middleware('can:asset-categories.create');
    Route::put('/asset-categories/{assetCategory}', [AssetCategoryController::class, 'update'])->middleware('can:asset-categories.edit');
    Route::delete('/asset-categories/{assetCategory}', [AssetCategoryController::class, 'destroy'])->middleware('can:asset-categories.delete');
    
    // Assets
    Route::middleware('can:assets.view')->group(function () {
        Route::get('/assets', [AssetController::class, 'index']);
        Route::get('/assets/summary', [AssetController::class, 'summary']);
        Route::get('/assets/download-import-template', [AssetController::class, 'downloadImportTemplate']);
        Route::get('/assets/{asset}', [AssetController::class, 'show']);
        Route::get('/assets/{id}/export-pdf', [AssetController::class, 'exportPdf']);
        Route::get('/assets/{id}/depreciation', [AssetController::class, 'depreciation']);
        
        // Asset Reports
        Route::prefix('asset-reports')->group(function () {
            Route::get('/category-breakdown', [AssetReportController::class, 'categoryBreakdown'])->middleware('can:assets.report');
            Route::get('/depreciation', [AssetReportController::class, 'depreciationReport'])->middleware('can:assets.report');
            Route::get('/by-status', [AssetReportController::class, 'statusReport'])->middleware('can:assets.report');
            Route::get('/by-acquisition-method', [AssetReportController::class, 'acquisitionMethodReport'])->middleware('can:assets.report');
            Route::get('/by-budget-type', [AssetReportController::class, 'budgetTypeReport'])->middleware('can:assets.report');
            Route::get('/expiring', [AssetReportController::class, 'expiringAssets'])->middleware('can:assets.report');
        });
    });

    Route::post('/assets', [AssetController::class, 'store'])->middleware('can:assets.create');
    Route::post('/assets/import', [AssetController::class, 'import'])->middleware('can:assets.create');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->middleware('can:assets.edit');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->middleware('can:assets.delete');
});



