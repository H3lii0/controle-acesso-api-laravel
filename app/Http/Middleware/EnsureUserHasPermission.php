<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->isCentralAdministrator() || $user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json([
            'code' => 'permission_required',
            'message' => 'Você não possui permissão para realizar esta ação.',
            'required_permission' => $permission,
        ], 403);
    }
}
