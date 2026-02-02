<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amphure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'code',
        'province_id'
    ];

    /**
     * Get the province that owns this amphure
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get all subdistricts for this amphure
     */
    public function subdistricts()
    {
        return $this->hasMany(Subdistrict::class);
    }
}
