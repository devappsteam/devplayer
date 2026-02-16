<?php

namespace App\Modules\Playlist\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Playlist\Models\Playlist;
use App\Modules\Playlist\Services\PlaylistService;
use App\Modules\Playlist\Repositories\PlaylistRepository;

class PlaylistServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlaylistService $service;
    protected PlaylistRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new Playlist();
        $this->repository = new PlaylistRepository($model);
        $this->service = new PlaylistService($this->repository);
    }

  
    public function test_service_can_retrieve_all_playlists(): void
    {
        Playlist::factory()->count(3)->create();

        $playlists = $this->service->all();

        $this->assertCount(3, $playlists);
    }

   
    public function test_service_can_paginate_playlists(): void
    {
        Playlist::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_playlist_by_id(): void
    {
        $playlist = Playlist::factory()->create();

        $found = $this->service->findById($playlist->id);

        $this->assertNotNull($found);
        $this->assertEquals($playlist->id, $found->id);
    }

  
    public function test_service_can_find_playlist_by_uuid(): void
    {
        $playlist = Playlist::factory()->create();

        $found = $this->service->findByUuid($playlist->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($playlist->uuid, $found->uuid);
    }


    public function test_service_can_create_playlist(): void
    {
        $data = [
            
        ];

        $playlist = $this->service->create($data);

        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $playlist->id,
        ]);
    }


    public function test_service_can_update_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($playlist, $data);

        $this->assertTrue($updated);
        $playlist->refresh();


    }

    public function test_service_can_delete_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        $deleted = $this->service->delete($playlist);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $playlist->id,
        ]);
    }

    public function test_service_can_restore_deleted_playlist(): void
    {
        $playlist = Playlist::factory()->create();
        $playlist->delete();

        $restored = $this->service->restore($playlist);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $playlist->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_playlist(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}