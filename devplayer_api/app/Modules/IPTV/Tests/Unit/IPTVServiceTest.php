<?php

namespace App\Modules\IPTV\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Services\IPTVService;
use App\Modules\IPTV\Repositories\IPTVRepository;

class IPTVServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IPTVService $service;
    protected IPTVRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new IPTV();
        $this->repository = new IPTVRepository($model);
        $this->service = new IPTVService($this->repository);
    }

  
    public function test_service_can_retrieve_all_iPTVs(): void
    {
        IPTV::factory()->count(3)->create();

        $iPTVs = $this->service->all();

        $this->assertCount(3, $iPTVs);
    }

   
    public function test_service_can_paginate_iPTVs(): void
    {
        IPTV::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_iPTV_by_id(): void
    {
        $iPTV = IPTV::factory()->create();

        $found = $this->service->findById($iPTV->id);

        $this->assertNotNull($found);
        $this->assertEquals($iPTV->id, $found->id);
    }

  
    public function test_service_can_find_iPTV_by_uuid(): void
    {
        $iPTV = IPTV::factory()->create();

        $found = $this->service->findByUuid($iPTV->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($iPTV->uuid, $found->uuid);
    }


    public function test_service_can_create_iPTV(): void
    {
        $data = [
            
        ];

        $iPTV = $this->service->create($data);

        $this->assertInstanceOf(IPTV::class, $iPTV);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $iPTV->id,
        ]);
    }


    public function test_service_can_update_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($iPTV, $data);

        $this->assertTrue($updated);
        $iPTV->refresh();


    }

    public function test_service_can_delete_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();

        $deleted = $this->service->delete($iPTV);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $iPTV->id,
        ]);
    }

    public function test_service_can_restore_deleted_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();
        $iPTV->delete();

        $restored = $this->service->restore($iPTV);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $iPTV->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_iPTV(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}