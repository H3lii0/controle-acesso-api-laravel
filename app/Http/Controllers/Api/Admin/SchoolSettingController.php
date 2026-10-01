<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchoolSettingRequest;
use App\Http\Resources\SchoolSettingResource;
use App\Models\SchoolSetting;
use Illuminate\Http\JsonResponse;

class SchoolSettingController extends Controller
{
    public function show(): SchoolSettingResource
    {
        return new SchoolSettingResource(SchoolSetting::current());
    }

    public function update(UpdateSchoolSettingRequest $request): JsonResponse
    {
        $setting = SchoolSetting::current();
        $setting->update($request->validated());

        return response()->json([
            'message' => 'Dados da escola atualizados com sucesso.',
            'data' => new SchoolSettingResource($setting->refresh()),
        ]);
    }
}
