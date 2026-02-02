<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'code',
        'postal_code',
        'amphure_id'
    ];

    /**
     * Get the amphure that owns this subdistrict
     */
    public function amphure()
    {
        return $this->belongsTo(Amphure::class);
    }
}
