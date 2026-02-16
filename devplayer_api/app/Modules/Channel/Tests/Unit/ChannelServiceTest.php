<?php

namespace App\Modules\Channel\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Services\ChannelService;
use App\Modules\Channel\Repositories\ChannelRepository;

class ChannelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChannelService $service;
    protected ChannelRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new Channel();
        $this->repository = new ChannelRepository($model);
        $this->service = new ChannelService($this->repository);
    }

  
    public function test_service_can_retrieve_all_channels(): void
    {
        Channel::factory()->count(3)->create();

        $channels = $this->service->all();

        $this->assertCount(3, $channels);
    }

   
    public function test_service_can_paginate_channels(): void
    {
        Channel::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_channel_by_id(): void
    {
        $channel = Channel::factory()->create();

        $found = $this->service->findById($channel->id);

        $this->assertNotNull($found);
        $this->assertEquals($channel->id, $found->id);
    }

  
    public function test_service_can_find_channel_by_uuid(): void
    {
        $channel = Channel::factory()->create();

        $found = $this->service->findByUuid($channel->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($channel->uuid, $found->uuid);
    }


    public function test_service_can_create_channel(): void
    {
        $data = [
            
        ];

        $channel = $this->service->create($data);

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $channel->id,
        ]);
    }


    public function test_service_can_update_channel(): void
    {
        $channel = Channel::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($channel, $data);

        $this->assertTrue($updated);
        $channel->refresh();


    }

    public function test_service_can_delete_channel(): void
    {
        $channel = Channel::factory()->create();

        $deleted = $this->service->delete($channel);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $channel->id,
        ]);
    }

    public function test_service_can_restore_deleted_channel(): void
    {
        $channel = Channel::factory()->create();
        $channel->delete();

        $restored = $this->service->restore($channel);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $channel->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_channel(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}