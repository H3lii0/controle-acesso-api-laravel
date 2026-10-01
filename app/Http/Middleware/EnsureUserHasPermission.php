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
    public function handle(Request $request, Closure $next, string $permission, string ...$alternativePermissions): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->isCentralAdministrator()) {
            return $next($request);
        }

        $permissions = [$permission, ...$alternativePermissions];

        foreach ($permissions as $permissionKey) {
            if ($user->hasPermission($permissionKey)) {
                return $next($request);
            }
        }

        return response()->json([
            'code' => 'permission_required',
            'message' => 'Você não possui permissão para realizar esta ação.',
            'required_permission' => $permission,
            ...($alternativePermissions !== [] ? ['required_permissions' => $permissions] : []),
        ], 403);
    }
}
