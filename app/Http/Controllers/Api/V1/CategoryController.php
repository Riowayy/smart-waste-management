<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\WasteCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = WasteCategory::query()
            ->orderBy('name')
            ->get();

        return $this->respond(
            CategoryResource::collection($categories)->resolve(),
            'Daftar kategori berhasil diambil.',
        );
    }
}
