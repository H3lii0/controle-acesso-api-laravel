<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuardianAccount
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->account_type !== AccountType::Guardian) {
            return response()->json([
                'code' => 'guardian_account_required',
                'message' => 'Esta área é exclusiva para responsáveis.',
            ], 403);
        }

        return $next($request);
    }
}
