<?php

namespace App\Modules\Stream\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Stream\Services\StreamService;
use App\Modules\Stream\Requests\StoreStreamRequest;
use App\Modules\Stream\Requests\UpdateStreamRequest;
use App\Modules\Stream\Resources\StreamResource;
use Illuminate\Http\JsonResponse;

class StreamController extends Controller
{
    use ApiResponse;

    protected StreamService $service;

    public function __construct(StreamService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $streams = $this->service->paginate(
            perPage: request('per_page', 15)
        );

        return $this->paginatedResponse(
            $streams,
            'Streams retrieved successfully'
        );
    }

    public function store(StoreStreamRequest $request): JsonResponse
    {
        $stream = $this->service->create($request->validated());

        return $this->createdResponse(
            new StreamResource($stream),
            'Stream created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $stream = $this->service->findByUuid($uuid);

        if (!$stream) {
            return $this->notFoundResponse('Stream not found');
        }

        return $this->successResponse(
            new StreamResource($stream),
            'Stream retrieved successfully'
        );
    }

    public function update(UpdateStreamRequest $request, string $uuid): JsonResponse
    {
        $stream = $this->service->findByUuid($uuid);

        if (!$stream) {
            return $this->notFoundResponse('Stream not found');
        }

        $updated = $this->service->update($stream, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update stream');
        }

        return $this->successResponse(
            new StreamResource($stream->fresh()),
            'Stream updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $stream = $this->service->findByUuid($uuid);

        if (!$stream) {
            return $this->notFoundResponse('Stream not found');
        }

        $deleted = $this->service->delete($stream);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete stream');
        }

        return $this->successResponse(
            null,
            'Stream deleted successfully'
        );
    }
}