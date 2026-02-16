<?php

namespace App\Modules\Channel\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Channel\Models\Channel;

class ChannelTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_returns_paginated_channels(): void
    {
        Channel::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/channels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'uuid', 'created_at', 'updated_at']
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_store_creates_new_channel(): void
    {
        $data = [
            // Add test data here
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/channels', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'uuid', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'uuid' => $response->json('data.uuid'),
        ]);
    }

    public function test_show_returns_specific_channel(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/channels/{$channel->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $channel->uuid,
                ],
            ]);
    }

    public function test_update_modifies_channel(): void
    {
        $channel = Channel::factory()->create();

        $data = [

        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/channels/{$channel->uuid}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $channel->refresh();


    }

    public function test_destroy_deletes_channel(): void
    {
        $channel = Channel::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/channels/{$channel->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $channel->id,
        ]);
    }


    public function test_show_returns_404_for_non_existent_channel(): void
    {
        $fakeUuid = $this->faker->uuid();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/channels/{$fakeUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Channel not found',
            ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/channels', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/channels');

        $response->assertStatus(401);
    }
}