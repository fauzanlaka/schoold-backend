<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'asset_name',
        'asset_code',
        'category_id',
        'gfmis_number',
        'acquisition_date',
        'document_number',
        'unit_price',
        'quantity',
        'budget_type',
        'acquisition_method',
        'useful_life_years',
        'depreciation_rate',
        'supplier_name',
        'supplier_phone',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'created_date',
        'updated_date',
    ];

    protected $casts = [
        // 'acquisition_date' => 'date',
        'unit_price' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
    ];

    protected $appends = ['encrypted_id', 'total_price', 'accumulated_depreciation', 'book_value'];

    // Status constants
    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    const STATUS_DISPOSED = 3;

    const STATUS_REPAIRING = 4;

    const STATUS_UNKNOWN = 5;

    // Budget type constants
    const BUDGET_GOVERNMENT = 1;

    const BUDGET_NON_GOVERNMENT = 2;

    const BUDGET_DONATION = 3;

    const BUDGET_OTHER = 4;

    // Acquisition method constants
    const ACQUISITION_SPECIFIC = 1;

    const ACQUISITION_SELECTION = 2;

    const ACQUISITION_BIDDING = 3;

    const ACQUISITION_SPECIAL = 4;

    const ACQUISITION_DONATION = 5;

    /**
     * Get the school that owns this asset
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the category of this asset
     */
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * Get the user who created this asset
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this asset
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get encrypted ID for security (prevent URL guessing)
     */
    public function getEncryptedIdAttribute(): string
    {
        return Crypt::encryptString((string) $this->id);
    }

    /**
     * Get effective useful life years (from asset or category)
     */
    public function getEffectiveUsefulLifeYearsAttribute()
    {
        return $this->useful_life_years ?? $this->category?->useful_life_years ?? 0;
    }

    /**
     * Get effective depreciation rate (from asset or category)
     */
    public function getEffectiveDepreciationRateAttribute()
    {
        return $this->depreciation_rate ?? $this->category?->depreciation_rate ?? 0;
    }

    /**
     * Computed: Total price = unit_price × quantity
     */
    public function getTotalPriceAttribute()
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    /**
     * Computed: Accumulated depreciation using straight-line method
     * ค่าเสื่อมสะสม = (ราคารวม × อัตราค่าเสื่อม / 100) × จำนวนปีที่ใช้งาน
     */
    public function getAccumulatedDepreciationAttribute()
    {
        if (! $this->acquisition_date) {
            return 0;
        }

        $totalPrice = $this->total_price;
        $rate = $this->effective_depreciation_rate;
        $maxLife = $this->effective_useful_life_years;

        if ($rate <= 0 || $maxLife <= 0) {
            return 0;
        }

        // Calculate years of usage (partial years count as full)
        $yearsUsed = Carbon::parse($this->acquisition_date)->diffInYears(Carbon::now());

        // Cannot exceed useful life
        $yearsUsed = min($yearsUsed, $maxLife);

        // Annual depreciation = Total Price × Rate / 100
        $annualDepreciation = $totalPrice * ($rate / 100);

        // Accumulated depreciation = Annual × Years (not exceeding total price)
        $accumulated = $annualDepreciation * $yearsUsed;

        return min(round($accumulated, 2), $totalPrice);
    }

    /**
     * Computed: Book value = total_price - accumulated_depreciation
     */
    public function getBookValueAttribute()
    {
        return max(0, round($this->total_price - $this->accumulated_depreciation, 2));
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_ACTIVE => 'ใช้งานอยู่',
            self::STATUS_INACTIVE => 'ไม่ได้ใช้งาน',
            self::STATUS_DISPOSED => 'จำหน่าย',
            self::STATUS_REPAIRING => 'กำลังซ่อมแซม',
            self::STATUS_UNKNOWN => 'ไม่ทราบสถานะ',
        ];

        return $labels[$this->status] ?? 'ไม่ระบุ';
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by school
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: Active assets only
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
