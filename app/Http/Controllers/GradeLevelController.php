<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use Illuminate\Http\Request;

class GradeLevelController extends Controller
{
    /**
     * Get all grade levels
     */
    public function index()
    {
        $gradeLevels = GradeLevel::orderBy('id')->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'grade_levels' => $gradeLevels
        ]);
    }
}
