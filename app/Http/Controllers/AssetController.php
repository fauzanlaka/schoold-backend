<?php

namespace App\Http\Controllers;

use App\Exports\AssetImportTemplateExport;
use App\Http\Resources\AssetCollection;
use App\Http\Resources\AssetResource;
use App\Http\Resources\AssetSummaryResource;
use App\Imports\AssetImport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\DepreciationService;
use App\Services\UserSchoolService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class AssetController extends Controller
{
    public function __construct(
        private UserSchoolService $userSchoolService
    ) {
    }

    /**
     * Get the current user's school
     */
    private function getUserSchool()
    {
        return $this->userSchoolService->getSchool();
    }

    /**
     * Display a listing of assets
     */
    public function index(Request $request)
    {
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $query = Asset::forSchool($school->id)
            ->with(['category:id,category_name,category_code', 'creator:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('asset_name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('gfmis_number', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Budget type filter
        if ($request->filled('budget_type')) {
            $query->where('budget_type', $request->budget_type);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('acquisition_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('acquisition_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $assets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new AssetCollection($assets),
        ]);
    }

    /**
     * Store a newly created asset
     */
    public function store(Request $request)
    {
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                }),
            ],
            'category_id' => [
                'required',
                'exists:asset_categories,id',
                function ($attribute, $value, $fail) use ($school) {
                    $exists = AssetCategory::where('id', $value)
                        ->where('school_id', $school->id)
                        ->exists();
                    if (!$exists) {
                        $fail('ประเภทครุภัณฑ์ไม่ถูกต้อง');
                    }
                },
            ],
            'gfmis_number' => 'nullable|string|max:100',
            'acquisition_date' => 'required|date',
            'document_number' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'budget_type' => 'nullable|integer|in:1,2,3,4',
            'acquisition_method' => 'nullable|integer|in:1,2,3,4,5',
            'useful_life_years' => 'nullable|integer|min:1|max:100',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'status' => 'integer|in:1,2,3,4,5',
            'notes' => 'nullable|string|max:2000',
        ], [
            'asset_name.required' => 'กรุณากรอกชื่อครุภัณฑ์',
            'asset_code.unique' => 'รหัสครุภัณฑ์นี้มีอยู่แล้ว',
            'category_id.required' => 'กรุณาเลือกประเภทครุภัณฑ์',
            'acquisition_date.required' => 'กรุณาระบุวันที่ได้มา',
            'unit_price.required' => 'กรุณากรอกราคาต่อหน่วย',
            'quantity.required' => 'กรุณากรอกจำนวน',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อม (%)',
        ]);

        $user = Auth::user();

        $asset = Asset::create([
            'school_id' => $school->id,
            'asset_name' => $validated['asset_name'],
            'asset_code' => !empty($validated['asset_code']) ? $validated['asset_code'] : null,
            'category_id' => $validated['category_id'],
            'gfmis_number' => $validated['gfmis_number'] ?? null,
            'acquisition_date' => $validated['acquisition_date'],
            'document_number' => $validated['document_number'] ?? null,
            'unit_price' => $validated['unit_price'],
            'quantity' => $validated['quantity'],
            'budget_type' => $validated['budget_type'] ?? null,
            'acquisition_method' => $validated['acquisition_method'] ?? null,
            'useful_life_years' => $validated['useful_life_years'] ?? null,
            'depreciation_rate' => $validated['depreciation_rate'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'supplier_phone' => $validated['supplier_phone'] ?? null,
            'status' => $validated['status'] ?? Asset::STATUS_ACTIVE,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_date' => now(),
            'updated_date' => now(),
        ]);

        $asset->load(['category:id,category_name,category_code', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มครุภัณฑ์สำเร็จ',
            'data' => new AssetResource($asset),
        ], 201);
    }

    /**
     * Display the specified asset
     */
    public function show($id)
    {
        $id = Crypt::decryptString($id);
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $asset = Asset::forSchool($school->id)
            ->with([
                'category:id,category_name,category_code,useful_life_years,depreciation_rate',
                'creator:id,name',
                'updater:id,name',
            ])
            ->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบครุภัณฑ์',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AssetResource($asset),
        ]);
    }

    /**
     * Update the specified asset
     */
    public function update(Request $request, $id)
    {
        $id = Crypt::decryptString($id);
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $asset = Asset::forSchool($school->id)->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบครุภัณฑ์',
            ], 404);
        }

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets')
                    ->where(function ($query) use ($school) {
                        return $query->where('school_id', $school->id);
                    })
                    ->ignore($id),
            ],
            'category_id' => [
                'required',
                'exists:asset_categories,id',
                function ($attribute, $value, $fail) use ($school) {
                    $exists = AssetCategory::where('id', $value)
                        ->where('school_id', $school->id)
                        ->exists();
                    if (!$exists) {
                        $fail('ประเภทครุภัณฑ์ไม่ถูกต้อง');
                    }
                },
            ],
            'gfmis_number' => 'nullable|string|max:100',
            'acquisition_date' => 'required|date',
            'document_number' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'budget_type' => 'nullable|integer|in:1,2,3,4',
            'acquisition_method' => 'nullable|integer|in:1,2,3,4,5',
            'useful_life_years' => 'nullable|integer|min:1|max:100',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'status' => 'integer|in:1,2,3,4,5',
            'notes' => 'nullable|string|max:2000',
        ], [
            'asset_name.required' => 'กรุณากรอกชื่อครุภัณฑ์',
            'asset_code.unique' => 'รหัสครุภัณฑ์นี้มีอยู่แล้ว',
            'category_id.required' => 'กรุณาเลือกประเภทครุภัณฑ์',
            'acquisition_date.required' => 'กรุณาระบุวันที่ได้มา',
            'unit_price.required' => 'กรุณากรอกราคาต่อหน่วย',
            'quantity.required' => 'กรุณากรอกจำนวน',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อม (%)',
        ]);

        // Prepare updated data
        $updateData = [
            'asset_name' => $validated['asset_name'],
            'asset_code' => !empty($validated['asset_code']) ? $validated['asset_code'] : null,
            'category_id' => $validated['category_id'],
            'gfmis_number' => $validated['gfmis_number'] ?? null,
            'acquisition_date' => $validated['acquisition_date'],
            'document_number' => $validated['document_number'] ?? null,
            'unit_price' => $validated['unit_price'],
            'quantity' => $validated['quantity'],
            'budget_type' => $validated['budget_type'] ?? null,
            'acquisition_method' => $validated['acquisition_method'] ?? null,
            'useful_life_years' => $validated['useful_life_years'] ?? null,
            'depreciation_rate' => $validated['depreciation_rate'] ?? null,
            'supplier_name' => $validated['supplier_name'] ?? null,
            'supplier_phone' => $validated['supplier_phone'] ?? null,
            'status' => $validated['status'] ?? $asset->status,
            'notes' => $validated['notes'] ?? null,
        ];

        // Capture original values before update
        $original = $asset->getOriginal();

        // Determine which fields would actually change
        $changedFields = [];
        foreach ($updateData as $key => $value) {
            $origValue = array_key_exists($key, $original) ? $original[$key] : null;

            // Special handling for date fields (Carbon object vs string comparison)
            if ($key === 'acquisition_date') {
                $origDateStr = $origValue instanceof \Carbon\Carbon
                    ? $origValue->format('Y-m-d')
                    : ($origValue ? date('Y-m-d', strtotime($origValue)) : null);
                $newDateStr = $value ? date('Y-m-d', strtotime($value)) : null;
                if ($origDateStr !== $newDateStr) {
                    $changedFields[] = $key;
                }

                continue;
            }

            // Use != to allow type-insensitive comparison for other fields
            if ($origValue != $value) {
                $changedFields[] = $key;
            }
        }

        // Only perform update (and set updated metadata) when something changed
        if (!empty($changedFields)) {
            $updateData['updated_by'] = Auth::id();
            $updateData['updated_date'] = now();

            $asset->update($updateData);
        }
        // If no changes, do nothing (don't touch updated_date)

        $asset->load(['category:id,category_name,category_code', 'creator:id,name', 'updater:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตครุภัณฑ์สำเร็จ',
            'data' => new AssetResource($asset),
        ]);
    }

    /**
     * Remove the specified asset (requires password confirmation)
     */
    public function destroy(Request $request, $id)
    {
        $id = Crypt::decryptString($id);
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        // Validate password
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'รหัสผ่านไม่ถูกต้อง',
            ], 422);
        }

        $asset = Asset::forSchool($school->id)->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบครุภัณฑ์',
            ], 404);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบครุภัณฑ์สำเร็จ',
        ]);
    }

    /**
     * Get summary statistics for dashboard
     */
    public function summary()
    {
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $assets = Asset::forSchool($school->id);

        // Total counts by status
        $statusCounts = Asset::forSchool($school->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Total value
        $totalValue = Asset::forSchool($school->id)
            ->selectRaw('SUM(unit_price * quantity) as total')
            ->value('total') ?? 0;

        // Category distribution
        $categoryStats = Asset::forSchool($school->id)
            ->select('category_id', DB::raw('count(*) as count'), DB::raw('SUM(unit_price * quantity) as value'))
            ->with('category:id,category_name')
            ->groupBy('category_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => new AssetSummaryResource([
                'total_assets' => $assets->count(),
                'status_counts' => $statusCounts,
                'total_value' => $totalValue,
                'category_stats' => $categoryStats,
            ]),
        ]);
    }

    /**
     * Get budget type label
     */
    private function getBudgetTypeLabel($type)
    {
        $labels = [
            Asset::BUDGET_GOVERNMENT => 'เงินงบประมาณ',
            Asset::BUDGET_NON_GOVERNMENT => 'เงินนอกงบประมาณ',
            Asset::BUDGET_DONATION => 'เงินบริจาค/เงินช่วยเหลือ',
            Asset::BUDGET_OTHER => 'อื่นๆ',
        ];

        return $labels[$type] ?? null;
    }

    /**
     * Get acquisition method label
     */
    private function getAcquisitionMethodLabel($method)
    {
        $labels = [
            Asset::ACQUISITION_SPECIFIC => 'วิธีเฉพาะเจาะจง',
            Asset::ACQUISITION_SELECTION => 'วิธีคัดเลือก',
            Asset::ACQUISITION_BIDDING => 'วิธีสอบราคา',
            Asset::ACQUISITION_SPECIAL => 'วิธีพิเศษ',
            Asset::ACQUISITION_DONATION => 'รับบริจาค',
        ];

        return $labels[$method] ?? null;
    }

    /**
     * Export Asset to PDF
     */
    public function exportPdf($id, DepreciationService $depreciationService)
    {
        $id = Crypt::decryptString($id);
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $asset = Asset::forSchool($school->id)
            ->with(['category'])
            ->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบครุภัณฑ์',
            ], 404);
        }

        // Use DepreciationService for correct calculation
        $depreciation = $depreciationService->calculate($asset);

        // Format purchase date
        $purchaseDate = Carbon::parse($asset->acquisition_date);
        $purchaseDateFormatted = $depreciationService->formatDateShort($purchaseDate);

        $data = [
            'asset' => $asset,
            'schoolName' => $school->school_name ?? '',
            'schoolAddress' => $school->full_address ?? '',
            'purchaseDateFormatted' => $purchaseDateFormatted,
            'totalValue' => $depreciation['total_value'],
            'usefulLife' => $depreciation['useful_life'],
            'depreciationRate' => $depreciation['depreciation_rate'],
            'annualDepreciation' => $depreciation['annual_depreciation'],
            'depreciationRows' => $depreciation['rows'],
        ];

        $pdf = \PDF::loadView('pdf.asset-register', $data, [], [
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'default_font_size' => 16,
            'default_font' => 'thsarabunnew',
        ]);

        $filename = 'asset-register-' . $asset->asset_code . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Get depreciation calculation for an asset
     *
     * This endpoint allows the frontend to get depreciation calculations from the backend
     * ensuring consistent calculation logic between frontend, PDF export, and API.
     */
    public function depreciation($id, DepreciationService $depreciationService)
    {
        $id = Crypt::decryptString($id);
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $asset = Asset::forSchool($school->id)
            ->with(['category'])
            ->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบครุภัณฑ์',
            ], 404);
        }

        $depreciation = $depreciationService->calculate($asset);

        // Add formatted purchase date
        if ($asset->acquisition_date) {
            $purchaseDate = Carbon::parse($asset->acquisition_date);
            $depreciation['purchase_date_formatted'] = $depreciationService->formatDateShort($purchaseDate);
        }

        return response()->json([
            'success' => true,
            'data' => $depreciation,
        ]);
    }

    /**
     * Download import template with category reference
     */
    public function downloadImportTemplate()
    {
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $filename = 'asset-import-template.xlsx';

        return Excel::download(
            new AssetImportTemplateExport($school->id),
            $filename
        );
    }

    /**
     * Import assets from Excel file
     */
    public function import(Request $request)
    {
        $school = $this->getUserSchool();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'กรุณาเลือกไฟล์',
            'file.mimes' => 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls)',
            'file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 5MB',
        ]);

        $user = Auth::user();
        $import = new AssetImport($school->id, $user->id);

        Excel::import($import, $request->file('file'));

        $response = [
            'success' => true,
            'message' => 'นำเข้าข้อมูลเสร็จสิ้น',
            'imported' => $import->getImportedCount(),
            'skipped' => $import->getSkippedCount(),
        ];

        if ($import->hasErrors()) {
            $response['errors'] = $import->getErrors();
        }

        return response()->json($response);
    }
}
