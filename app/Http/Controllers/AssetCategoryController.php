<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Services\UserSchoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    public function __construct(
        private UserSchoolService $userSchoolService
    ) {}

    /**
     * Get the current user's school
     */
    private function getUserSchool()
    {
        return $this->userSchoolService->getSchool();
    }

    /**
     * Display a listing of asset categories
     */
    public function index(Request $request)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $query = AssetCategory::forSchool($school->id)
            ->with('creator:id,name');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('category_name', 'like', "%{$search}%")
                  ->orWhere('category_code', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'category_name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })
            ],
            'category_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('asset_categories')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })
            ],
            'useful_life_years' => 'required|integer|min:1|max:100',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'category_name.required' => 'กรุณากรอกชื่อประเภทครุภัณฑ์',
            'category_name.unique' => 'ชื่อประเภทครุภัณฑ์นี้มีอยู่แล้ว',
            'category_code.unique' => 'รหัสประเภทนี้มีอยู่แล้ว',
            'useful_life_years.required' => 'กรุณากรอกอายุการใช้งาน',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อมราคา',
        ]);

        $user = Auth::user();
        
        $category = AssetCategory::create([
            'school_id' => $school->id,
            'category_name' => $validated['category_name'],
            'category_code' => $validated['category_code'] ?? null,
            'useful_life_years' => $validated['useful_life_years'],
            'depreciation_rate' => $validated['depreciation_rate'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $category->load('creator:id,name');

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มประเภทครุภัณฑ์สำเร็จ',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $category = AssetCategory::forSchool($school->id)
            ->with(['creator:id,name', 'updater:id,name'])
            ->withCount('assets')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบประเภทครุภัณฑ์'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $category = AssetCategory::forSchool($school->id)->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบประเภทครุภัณฑ์'
            ], 404);
        }

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories')
                    ->where(function ($query) use ($school) {
                        return $query->where('school_id', $school->id);
                    })
                    ->ignore($id)
            ],
            'category_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('asset_categories')
                    ->where(function ($query) use ($school) {
                        return $query->where('school_id', $school->id);
                    })
                    ->ignore($id)
            ],
            'useful_life_years' => 'required|integer|min:1|max:100',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'category_name.required' => 'กรุณากรอกชื่อประเภทครุภัณฑ์',
            'category_name.unique' => 'ชื่อประเภทครุภัณฑ์นี้มีอยู่แล้ว',
            'category_code.unique' => 'รหัสประเภทนี้มีอยู่แล้ว',
            'useful_life_years.required' => 'กรุณากรอกอายุการใช้งาน',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อมราคา',
        ]);

        $category->update([
            'category_name' => $validated['category_name'],
            'category_code' => $validated['category_code'] ?? null,
            'useful_life_years' => $validated['useful_life_years'],
            'depreciation_rate' => $validated['depreciation_rate'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $category->is_active,
            'updated_by' => Auth::id(),
        ]);

        $category->load(['creator:id,name', 'updater:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตประเภทครุภัณฑ์สำเร็จ',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $category = AssetCategory::forSchool($school->id)
            ->withCount('assets')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบประเภทครุภัณฑ์'
            ], 404);
        }

        // Check if category has assets
        if ($category->assets_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีครุภัณฑ์ในประเภทนี้อยู่ ' . $category->assets_count . ' รายการ'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบประเภทครุภัณฑ์สำเร็จ'
        ]);
    }

    /**
     * Get all active categories (for dropdown)
     */
    public function listActive()
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $categories = AssetCategory::forSchool($school->id)
            ->active()
            ->select('id', 'category_name', 'category_code', 'useful_life_years', 'depreciation_rate')
            ->orderBy('category_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Import asset categories from Excel file
     */
    public function import(Request $request)
    {
        $school = $this->getUserSchool();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน'
            ], 404);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120'
        ], [
            'file.required' => 'กรุณาเลือกไฟล์',
            'file.mimes' => 'ไฟล์ต้องเป็นประเภท xlsx, xls หรือ csv',
            'file.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB'
        ]);

        $user = Auth::user();
        $import = new \App\Imports\AssetCategoryImport($school->id, $user->id);
        
        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
            
            $response = [
                'success' => true,
                'message' => 'นำเข้าข้อมูลสำเร็จ',
                'imported' => $import->getImportedCount(),
                'skipped' => $import->getSkippedCount(),
            ];

            if ($import->hasErrors()) {
                $response['errors'] = $import->getErrors();
                $response['message'] = "นำเข้าสำเร็จ {$import->getImportedCount()} รายการ, ข้ามไป {$import->getSkippedCount()} รายการ";
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template Excel file
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AssetCategoryTemplateExport(),
            'asset_category_template.xlsx'
        );
    }
}
