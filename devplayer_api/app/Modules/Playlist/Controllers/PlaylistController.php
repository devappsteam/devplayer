<?php

namespace App\Modules\Playlist\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Playlist\Services\PlaylistService;
use App\Modules\Playlist\Requests\StorePlaylistRequest;
use App\Modules\Playlist\Requests\UpdatePlaylistRequest;
use App\Modules\Playlist\Resources\PlaylistResource;
use Illuminate\Http\JsonResponse;

class PlaylistController extends Controller
{
    use ApiResponse;

    protected PlaylistService $service;

    public function __construct(PlaylistService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $playlists = $this->service->paginate(
            perPage: request('per_page', 15)
        );

        return $this->paginatedResponse(
            $playlists,
            'Playlists retrieved successfully'
        );
    }

    public function store(StorePlaylistRequest $request): JsonResponse
    {
        $playlist = $this->service->create($request->validated());

        return $this->createdResponse(
            new PlaylistResource($playlist),
            'Playlist created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $playlist = $this->service->findByUuid($uuid);

        if (!$playlist) {
            return $this->notFoundResponse('Playlist not found');
        }

        return $this->successResponse(
            new PlaylistResource($playlist),
            'Playlist retrieved successfully'
        );
    }

    public function update(UpdatePlaylistRequest $request, string $uuid): JsonResponse
    {
        $playlist = $this->service->findByUuid($uuid);

        if (!$playlist) {
            return $this->notFoundResponse('Playlist not found');
        }

        $updated = $this->service->update($playlist, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update playlist');
        }

        return $this->successResponse(
            new PlaylistResource($playlist->fresh()),
            'Playlist updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $playlist = $this->service->findByUuid($uuid);

        if (!$playlist) {
            return $this->notFoundResponse('Playlist not found');
        }

        $deleted = $this->service->delete($playlist);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete playlist');
        }

        return $this->successResponse(
            null,
            'Playlist deleted successfully'
        );
    }
}