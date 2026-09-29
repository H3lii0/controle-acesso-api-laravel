<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->account_status !== AccountStatus::Active) {
            return response()->json([
                'code' => $user->account_status === AccountStatus::PendingActivation
                    ? 'account_pending_activation'
                    : 'account_disabled',
                'message' => $user->account_status === AccountStatus::PendingActivation
                    ? 'Esta conta ainda precisa ser ativada.'
                    : 'Esta conta está desativada.',
            ], 403);
        }

        return $next($request);
    }
}
