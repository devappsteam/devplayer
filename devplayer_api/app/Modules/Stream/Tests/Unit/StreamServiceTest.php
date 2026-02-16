<?php

namespace App\Modules\Stream\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Stream\Models\Stream;
use App\Modules\Stream\Services\StreamService;
use App\Modules\Stream\Repositories\StreamRepository;

class StreamServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StreamService $service;
    protected StreamRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new Stream();
        $this->repository = new StreamRepository($model);
        $this->service = new StreamService($this->repository);
    }

  
    public function test_service_can_retrieve_all_streams(): void
    {
        Stream::factory()->count(3)->create();

        $streams = $this->service->all();

        $this->assertCount(3, $streams);
    }

   
    public function test_service_can_paginate_streams(): void
    {
        Stream::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_stream_by_id(): void
    {
        $stream = Stream::factory()->create();

        $found = $this->service->findById($stream->id);

        $this->assertNotNull($found);
        $this->assertEquals($stream->id, $found->id);
    }

  
    public function test_service_can_find_stream_by_uuid(): void
    {
        $stream = Stream::factory()->create();

        $found = $this->service->findByUuid($stream->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($stream->uuid, $found->uuid);
    }


    public function test_service_can_create_stream(): void
    {
        $data = [
            
        ];

        $stream = $this->service->create($data);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $stream->id,
        ]);
    }


    public function test_service_can_update_stream(): void
    {
        $stream = Stream::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($stream, $data);

        $this->assertTrue($updated);
        $stream->refresh();


    }

    public function test_service_can_delete_stream(): void
    {
        $stream = Stream::factory()->create();

        $deleted = $this->service->delete($stream);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $stream->id,
        ]);
    }

    public function test_service_can_restore_deleted_stream(): void
    {
        $stream = Stream::factory()->create();
        $stream->delete();

        $restored = $this->service->restore($stream);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $stream->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_stream(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}