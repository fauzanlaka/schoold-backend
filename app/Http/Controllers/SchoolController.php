<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class SchoolController extends Controller
{
    public function __construct(private PermissionRegistrar $permissionRegistrar) {}

    /**
     * Check if current user has registered a school
     */
    public function checkRegistration()
    {
        $user = Auth::user();
        $school = School::where('created_by', $user->id)->first();

        return response()->json([
            'success' => true,
            'has_school' => $school !== null,
            'school_id' => $school?->id,
        ]);
    }

    /**
     * Get the school profile for the current user
     */
    public function show()
    {
        $user = Auth::user();
        $school = School::with(['gradeLevels', 'province', 'amphure', 'subdistrict'])
            ->where('created_by', $user->id)
            ->first();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน กรุณาลงทะเบียนโรงเรียนก่อน',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'school' => $school,
        ]);
    }

    /**
     * Register a new school
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user already has a school
        $existingSchool = School::where('created_by', $user->id)->first();
        if ($existingSchool) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้ลงทะเบียนโรงเรียนแล้ว หนึ่งบัญชีสามารถลงทะเบียนได้เพียง 1 โรงเรียนเท่านั้น',
            ], 400);
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:50|unique:schools,school_code',
            'address' => 'nullable|string|max:255',
            'road' => 'nullable|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'amphure_id' => 'required|exists:amphures,id',
            'district_id' => 'required|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:schools,email',
            'grade_levels' => 'required|array|min:1',
            'grade_levels.*' => 'exists:grade_levels,id',
        ], [
            'school_name.required' => 'กรุณากรอกชื่อโรงเรียน',
            'school_code.required' => 'กรุณากรอกรหัสโรงเรียน',
            'school_code.unique' => 'รหัสโรงเรียนนี้ถูกใช้งานแล้ว',
            'province_id.required' => 'กรุณาเลือกจังหวัด',
            'amphure_id.required' => 'กรุณาเลือกอำเภอ/เขต',
            'district_id.required' => 'กรุณาเลือกตำบล/แขวง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'grade_levels.required' => 'กรุณาเลือกระดับชั้นที่เปิดสอนอย่างน้อย 1 ระดับ',
        ]);

        DB::beginTransaction();
        try {
            $school = School::create([
                'school_name' => $validated['school_name'],
                'school_code' => $validated['school_code'],
                'address' => $validated['address'] ?? null,
                'road' => $validated['road'] ?? null,
                'province_id' => $validated['province_id'],
                'amphure_id' => $validated['amphure_id'],
                'district_id' => $validated['district_id'],
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'registered_at' => now(),
            ]);

            // Attach grade levels
            $school->gradeLevels()->attach($validated['grade_levels']);

            // Attach user to school via pivot table
            $school->users()->attach($user->id, ['is_active' => true]);

            // Set team ID for role assignment (school-scoped permissions)
            $this->permissionRegistrar->setPermissionsTeamId($school->id);

            // Assign school-admin role to the school creator with school_id
            $user->assignRole('school-admin');

            DB::commit();

            // Load relationships for response
            $school->load(['gradeLevels', 'province', 'amphure', 'subdistrict']);

            return response()->json([
                'success' => true,
                'message' => 'ลงทะเบียนโรงเรียนสำเร็จ',
                'school' => $school,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลงทะเบียน: '.$e->getMessage().'school_id: '.$school->id,
            ], 500);
        }
    }

    /**
     * Update school profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $school = School::where('created_by', $user->id)->first();

        if (! $school) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลโรงเรียน',
            ], 404);
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => ['required', 'string', 'max:50', Rule::unique('schools')->ignore($school->id)],
            'address' => 'nullable|string|max:255',
            'road' => 'nullable|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'amphure_id' => 'required|exists:amphures,id',
            'district_id' => 'required|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('schools')->ignore($school->id)],
            'grade_levels' => 'required|array|min:1',
            'grade_levels.*' => 'exists:grade_levels,id',
        ], [
            'school_name.required' => 'กรุณากรอกชื่อโรงเรียน',
            'school_code.required' => 'กรุณากรอกรหัสโรงเรียน',
            'school_code.unique' => 'รหัสโรงเรียนนี้ถูกใช้งานแล้ว',
            'province_id.required' => 'กรุณาเลือกจังหวัด',
            'amphure_id.required' => 'กรุณาเลือกอำเภอ/เขต',
            'district_id.required' => 'กรุณาเลือกตำบล/แขวง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'grade_levels.required' => 'กรุณาเลือกระดับชั้นที่เปิดสอนอย่างน้อย 1 ระดับ',
        ]);

        DB::beginTransaction();
        try {
            $school->update([
                'school_name' => $validated['school_name'],
                'school_code' => $validated['school_code'],
                'address' => $validated['address'] ?? null,
                'road' => $validated['road'] ?? null,
                'province_id' => $validated['province_id'],
                'amphure_id' => $validated['amphure_id'],
                'district_id' => $validated['district_id'],
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'updated_by' => $user->id,
                'last_edited_at' => now(),
            ]);

            // Sync grade levels
            $school->gradeLevels()->sync($validated['grade_levels']);

            DB::commit();

            // Load relationships for response
            $school->load(['gradeLevels', 'province', 'amphure', 'subdistrict']);

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลโรงเรียนสำเร็จ',
                'school' => $school,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดต: '.$e->getMessage(),
            ], 500);
        }
    }
}
