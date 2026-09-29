<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SchoolClassOptionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SchoolClassResource::collection(
            SchoolClass::query()
                ->where('is_active', true)
                ->orderBy('shift')
                ->orderBy('name')
                ->get(),
        );
    }
}
