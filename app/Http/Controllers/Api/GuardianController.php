<?php

namespace App\Http\Controllers\Api;

use App\Actions\IssueAccountActivation;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\IndexGuardianRequest;
use App\Http\Requests\Guardian\UpdateGuardianRequest;
use App\Http\Resources\GuardianResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuardianController extends Controller
{
    public function __construct(private readonly IssueAccountActivation $issueAccountActivation) {}

    public function index(IndexGuardianRequest $request): AnonymousResourceCollection
    {
        $search = '%'.$request->string('search')->toString().'%';

        return GuardianResource::collection(
            User::query()
                ->where('account_type', AccountType::Guardian)
                ->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereLike('full_name', $search, caseSensitive: false)
                        ->orWhereLike('email', $search, caseSensitive: false);
                })
                ->withCount('guardedStudents')
                ->orderBy('full_name')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString(),
        );
    }

    public function update(UpdateGuardianRequest $request, User $guardian): GuardianResource|JsonResponse
    {
        if (! $this->isGuardian($guardian)) {
            return $this->guardianNotFoundResponse();
        }

        $validated = $request->validated();
        $emailChanged = $guardian->email !== $validated['email'];

        if ($emailChanged && $guardian->account_status !== AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'guardian_email_change_requires_verification',
                'message' => 'O e-mail de uma conta já ativada exige um fluxo próprio de verificação.',
            ], 409);
        }

        $guardian->update($validated);

        if ($emailChanged) {
            $this->issueAccountActivation->handle($guardian);
        }

        return (new GuardianResource($guardian->loadCount('guardedStudents')))
            ->additional([
                'message' => $emailChanged
                    ? 'Responsável atualizado. Um novo convite foi enviado ao novo e-mail.'
                    : 'Responsável atualizado com sucesso.',
            ]);
    }

    public function resendInvitation(User $guardian): JsonResponse
    {
        if (! $this->isGuardian($guardian)) {
            return $this->guardianNotFoundResponse();
        }

        if ($guardian->account_status !== AccountStatus::PendingActivation) {
            return response()->json([
                'code' => 'guardian_not_pending_activation',
                'message' => 'Somente contas pendentes podem receber um novo convite.',
            ], 409);
        }

        $this->issueAccountActivation->handle($guardian);

        return response()->json([
            'message' => 'Um novo convite de ativação foi enviado ao responsável.',
        ]);
    }

    private function isGuardian(User $user): bool
    {
        return $user->account_type === AccountType::Guardian;
    }

    private function guardianNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'code' => 'guardian_not_found',
            'message' => 'Responsável não encontrado.',
        ], 404);
    }
}
