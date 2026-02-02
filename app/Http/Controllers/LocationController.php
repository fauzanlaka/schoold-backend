<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Amphure;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get all provinces
     */
    public function getProvinces()
    {
        $provinces = Province::orderBy('name_th')->get(['id', 'name_th', 'name_en']);

        return response()->json([
            'success' => true,
            'provinces' => $provinces
        ]);
    }

    /**
     * Get amphures by province
     */
    public function getAmphures($province_id)
    {
        $amphures = Amphure::where('province_id', $province_id)
            ->orderBy('name_th')
            ->get(['id', 'name_th', 'name_en']);

        return response()->json([
            'success' => true,
            'amphures' => $amphures
        ]);
    }

    /**
     * Get subdistricts by amphure
     */
    public function getSubdistricts($amphure_id)
    {
        $subdistricts = Subdistrict::where('amphure_id', $amphure_id)
            ->orderBy('name_th')
            ->get(['id', 'name_th', 'name_en', 'postal_code']);

        return response()->json([
            'success' => true,
            'subdistricts' => $subdistricts
        ]);
    }
}
