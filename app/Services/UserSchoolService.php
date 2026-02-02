<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Auth;

class UserSchoolService
{
    /**
     * Get the current authenticated user's school
     *
     * This service provides a centralized way to get the school
     * associated with the currently logged-in user.
     *
     * Priority:
     * 1. First active school from school_user pivot table
     * 2. School where user is the creator (legacy fallback)
     */
    public function getSchool(): ?School
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // First, check school_user pivot table for active membership
        $school = $user->schools()
            ->wherePivot('is_active', true)
            ->first();

        if ($school) {
            return $school;
        }

        // Fallback: Get school where the user is the creator (for backwards compatibility)
        return School::where('created_by', $user->id)->first();
    }

    /**
     * Get all schools the user belongs to
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSchools()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $user->schools()->wherePivot('is_active', true)->get();
    }

    /**
     * Get the current user's school ID
     */
    public function getSchoolId(): ?int
    {
        $school = $this->getSchool();

        return $school?->id;
    }

    /**
     * Check if the current user has a school
     */
    public function hasSchool(): bool
    {
        return $this->getSchool() !== null;
    }

    /**
     * Check if user belongs to a specific school
     */
    public function belongsToSchool(int $schoolId): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->schools()
            ->wherePivot('is_active', true)
            ->where('schools.id', $schoolId)
            ->exists();
    }
}
