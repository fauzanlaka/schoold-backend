<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code'
    ];

    /**
     * Get all schools that have this grade level
     */
    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_grade_level');
    }
}
