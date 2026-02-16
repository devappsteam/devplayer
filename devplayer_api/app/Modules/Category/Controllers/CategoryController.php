<?php

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Category\Requests\StoreCategoryRequest;
use App\Modules\Category\Requests\UpdateCategoryRequest;
use App\Modules\Category\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    protected CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $perPage = request('per_page', 15);

        // Se houver filtro por tipo, aplicar
        if (request()->has('type')) {
            $categories = $this->service->getByType(
                request('type'),
                $perPage
            );
        } else {
            $categories = $this->service->paginate(
                perPage: $perPage
            );
        }

        return $this->paginatedResponse(
            $categories,
            'Categories retrieved successfully',
            CategoryResource::class
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create($request->validated());

        return $this->createdResponse(
            new CategoryResource($category),
            'Category created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $category = $this->service->findByUuid($uuid);

        if (!$category) {
            return $this->notFoundResponse('Category not found');
        }

        return $this->successResponse(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }

    public function update(UpdateCategoryRequest $request, string $uuid): JsonResponse
    {
        $category = $this->service->findByUuid($uuid);

        if (!$category) {
            return $this->notFoundResponse('Category not found');
        }

        $updated = $this->service->update($category, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update category');
        }

        return $this->successResponse(
            new CategoryResource($category->fresh()),
            'Category updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $category = $this->service->findByUuid($uuid);

        if (!$category) {
            return $this->notFoundResponse('Category not found');
        }

        $deleted = $this->service->delete($category);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete category');
        }

        return $this->successResponse(
            null,
            'Category deleted successfully'
        );
    }
}
