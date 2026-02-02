<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionTeam
{
    /**
     * Handle an incoming request.
     * Sets the Spatie Permission team ID based on user's current school.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user) {
            // Get user's first school (or you can use session/header to select active school)
            $currentSchool = $user->schools()->first();
            
            if ($currentSchool) {
                setPermissionsTeamId($currentSchool->id);
            }
        }
        
        return $next($request);
    }
}
