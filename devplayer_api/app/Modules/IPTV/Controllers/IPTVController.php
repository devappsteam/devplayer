<?php

namespace App\Modules\IPTV\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\IPTV\Services\IPTVService;
use App\Modules\IPTV\Requests\StoreIPTVRequest;
use App\Modules\IPTV\Requests\UpdateIPTVRequest;
use App\Modules\IPTV\Resources\IPTVResource;
use Illuminate\Http\JsonResponse;

class IPTVController extends Controller
{
    use ApiResponse;

    protected IPTVService $service;

    public function __construct(IPTVService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $iPTVs = $this->service->paginate(
            perPage: request('per_page', 15)
        );

        return $this->paginatedResponse(
            $iPTVs,
            'IPTVS retrieved successfully'
        );
    }

    public function store(StoreIPTVRequest $request): JsonResponse
    {
        $iPTV = $this->service->create($request->validated());

        return $this->createdResponse(
            new IPTVResource($iPTV),
            'IPTV created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $iPTV = $this->service->findByUuid($uuid);

        if (!$iPTV) {
            return $this->notFoundResponse('IPTV not found');
        }

        return $this->successResponse(
            new IPTVResource($iPTV),
            'IPTV retrieved successfully'
        );
    }

    public function update(UpdateIPTVRequest $request, string $uuid): JsonResponse
    {
        $iPTV = $this->service->findByUuid($uuid);

        if (!$iPTV) {
            return $this->notFoundResponse('IPTV not found');
        }

        $updated = $this->service->update($iPTV, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update iPTV');
        }

        return $this->successResponse(
            new IPTVResource($iPTV->fresh()),
            'IPTV updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $iPTV = $this->service->findByUuid($uuid);

        if (!$iPTV) {
            return $this->notFoundResponse('IPTV not found');
        }

        $deleted = $this->service->delete($iPTV);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete iPTV');
        }

        return $this->successResponse(
            null,
            'IPTV deleted successfully'
        );
    }

    /**
     * Test IPTV connection
     */
    public function testConnection(): JsonResponse
    {
        $validated = request()->validate([
            'url' => 'required|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->service->testConnection($validated);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Connection failed', 400);
        }

        return $this->successResponse(
            $result,
            'Connection successful'
        );
    }

    /**
     * Synchronize IPTV data asynchronously
     */
    public function synchronize(string $uuid): JsonResponse
    {
        $iPTV = $this->service->findByUuid($uuid);

        if (!$iPTV) {
            return $this->notFoundResponse('IPTV not found');
        }

        // Get optional types filter from request
        $types = request('types'); // Can be 'live', 'vod', 'series' or array

        $result = $this->service->synchronizeAsync($iPTV, $types);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Synchronization failed', 400);
        }

        return $this->successResponse(
            $result,
            'IPTV synchronization started'
        );
    }

    /**
     * Get synchronization progress
     */
    public function syncProgress(string $syncId): JsonResponse
    {
        $result = $this->service->getSyncProgress($syncId);

        if (!$result['success']) {
            return $this->notFoundResponse($result['message'] ?? 'Sync progress not found');
        }

        return $this->successResponse(
            $result,
            'Sync progress retrieved successfully'
        );
    }

    /**
     * Synchronize IPTV data (synchronous - for CLI or direct calls)
     */
    public function synchronizeSync(string $uuid): JsonResponse
    {
        $iPTV = $this->service->findByUuid($uuid);

        if (!$iPTV) {
            return $this->notFoundResponse('IPTV not found');
        }

        $result = $this->service->synchronize($iPTV);

        if (!$result['success']) {
            return $this->errorResponse($result['error'] ?? 'Synchronization failed', 400);
        }

        return $this->successResponse(
            $result,
            'IPTV synchronized successfully'
        );
    }

    /**
     * Get IPTV with statistics
     */
    public function stats(string $uuid): JsonResponse
    {
        $result = $this->service->getWithStats($uuid);

        if (!$result) {
            return $this->notFoundResponse('IPTV not found');
        }

        return $this->successResponse(
            $result,
            'IPTV statistics retrieved successfully'
        );
    }
}
