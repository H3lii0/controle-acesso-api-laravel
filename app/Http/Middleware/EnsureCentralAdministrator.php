<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralAdministrator
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isActive() || ! $user->isCentralAdministrator()) {
            return response()->json([
                'code' => 'central_administrator_required',
                'message' => 'Esta ação é exclusiva do administrador central.',
            ], 403);
        }

        return $next($request);
    }
}
