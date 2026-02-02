<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'school_code',
        'address',
        'road',
        'district_id',
        'amphure_id',
        'province_id',
        'postal_code',
        'phone',
        'email',
        'created_by',
        'updated_by',
        'registered_at',
        'last_edited_at'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_edited_at' => 'datetime',
    ];

    /**
     * Get the user who created this school
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this school
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the grade levels for this school
     */
    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class, 'school_grade_level');
    }

    /**
     * Get the province
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the amphure (district)
     */
    public function amphure()
    {
        return $this->belongsTo(Amphure::class);
    }

    /**
     * Get the subdistrict
     */
    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'district_id');
    }

    /**
     * Get the full address of the school
     * Combines address, road, subdistrict, amphure, province, and postal code
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];

        if ($this->address) {
            $parts[] = $this->address;
        }

        if ($this->road) {
            $parts[] = 'ถ.' . $this->road;
        }

        if ($this->subdistrict && $this->subdistrict->name_th) {
            $parts[] = 'ต.' . $this->subdistrict->name_th;
        }

        if ($this->amphure && $this->amphure->name_th) {
            $parts[] = 'อ.' . $this->amphure->name_th;
        }

        if ($this->province && $this->province->name_th) {
            $parts[] = 'จ.' . $this->province->name_th;
        }

        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }

        return implode(' ', $parts);
    }

    /**
     * Get the users that belong to this school
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user')
            ->withPivot('is_active')
            ->withTimestamps();
    }
}
