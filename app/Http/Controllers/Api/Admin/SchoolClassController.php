<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexSchoolClassRequest;
use App\Http\Requests\Admin\StoreSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSchoolClassStatusRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SchoolClassController extends Controller
{
    public function index(IndexSchoolClassRequest $request): AnonymousResourceCollection
    {
        $query = SchoolClass::query();

        if ($request->filled('search')) {
            $query->whereLike(
                'name',
                '%'.$request->string('search')->toString().'%',
                caseSensitive: false,
            );
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->string('shift')->toString());
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return SchoolClassResource::collection(
            $query
                ->orderBy('shift')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 15))
                ->withQueryString(),
        );
    }

    public function store(StoreSchoolClassRequest $request): JsonResponse
    {
        $schoolClass = SchoolClass::query()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Turma cadastrada com sucesso.',
            'data' => new SchoolClassResource($schoolClass),
        ], 201);
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass): SchoolClassResource
    {
        $schoolClass->update($request->validated());

        return (new SchoolClassResource($schoolClass))
            ->additional(['message' => 'Turma atualizada com sucesso.']);
    }

    public function updateStatus(
        UpdateSchoolClassStatusRequest $request,
        SchoolClass $schoolClass,
    ): SchoolClassResource {
        $schoolClass->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return (new SchoolClassResource($schoolClass))
            ->additional([
                'message' => $schoolClass->is_active
                    ? 'Turma ativada com sucesso.'
                    : 'Turma desativada com sucesso.',
            ]);
    }
}
