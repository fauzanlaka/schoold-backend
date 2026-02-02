<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'code'
    ];

    /**
     * Get all amphures for this province
     */
    public function amphures()
    {
        return $this->hasMany(Amphure::class);
    }
}
