<?php

namespace App\Http\Middleware;

use App\Enums\TeamRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentTeamMembership
{
    /**
     * Require a valid current-team membership and, when supplied, a minimum role.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_if(
            $user === null ||
            $team === null ||
            ! $user->belongsToTeam($team),
            403,
        );

        if ($minimumRole !== null) {
            $requiredRole = TeamRole::tryFrom($minimumRole);
            $currentRole = $user->teamRole($team);

            abort_if(
                $requiredRole === null ||
                $currentRole === null ||
                ! $currentRole->isAtLeast($requiredRole),
                403,
            );
        }

        return $next($request);
    }
}
