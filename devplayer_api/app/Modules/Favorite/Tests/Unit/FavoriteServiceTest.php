<?php

namespace App\Modules\Favorite\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Favorite\Services\FavoriteService;
use App\Modules\Favorite\Repositories\FavoriteRepository;

class FavoriteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FavoriteService $service;
    protected FavoriteRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new Favorite();
        $this->repository = new FavoriteRepository($model);
        $this->service = new FavoriteService($this->repository);
    }

  
    public function test_service_can_retrieve_all_favorites(): void
    {
        Favorite::factory()->count(3)->create();

        $favorites = $this->service->all();

        $this->assertCount(3, $favorites);
    }

   
    public function test_service_can_paginate_favorites(): void
    {
        Favorite::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_favorite_by_id(): void
    {
        $favorite = Favorite::factory()->create();

        $found = $this->service->findById($favorite->id);

        $this->assertNotNull($found);
        $this->assertEquals($favorite->id, $found->id);
    }

  
    public function test_service_can_find_favorite_by_uuid(): void
    {
        $favorite = Favorite::factory()->create();

        $found = $this->service->findByUuid($favorite->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($favorite->uuid, $found->uuid);
    }


    public function test_service_can_create_favorite(): void
    {
        $data = [
            
        ];

        $favorite = $this->service->create($data);

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $favorite->id,
        ]);
    }


    public function test_service_can_update_favorite(): void
    {
        $favorite = Favorite::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($favorite, $data);

        $this->assertTrue($updated);
        $favorite->refresh();


    }

    public function test_service_can_delete_favorite(): void
    {
        $favorite = Favorite::factory()->create();

        $deleted = $this->service->delete($favorite);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $favorite->id,
        ]);
    }

    public function test_service_can_restore_deleted_favorite(): void
    {
        $favorite = Favorite::factory()->create();
        $favorite->delete();

        $restored = $this->service->restore($favorite);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $favorite->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_favorite(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}