<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'category_name',
        'category_code',
        'useful_life_years',
        'depreciation_rate',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'created_date',
        'updated_date'
    ];

    protected $casts = [
        'depreciation_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that owns this category
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created this category
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this category
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the assets in this category
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    /**
     * Scope: Active categories only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by school
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
