<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(
            Permission::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        );
    }
}
