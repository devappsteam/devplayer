<?php

namespace App\Modules\Stream\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Stream\Models\Stream;

class StreamTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_returns_paginated_streams(): void
    {
        Stream::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/streams');

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

    public function test_store_creates_new_stream(): void
    {
        $data = [
            // Add test data here
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/streams', $data);

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

    public function test_show_returns_specific_stream(): void
    {
        $stream = Stream::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/streams/{$stream->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $stream->uuid,
                ],
            ]);
    }

    public function test_update_modifies_stream(): void
    {
        $stream = Stream::factory()->create();

        $data = [

        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/streams/{$stream->uuid}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $stream->refresh();


    }

    public function test_destroy_deletes_stream(): void
    {
        $stream = Stream::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/streams/{$stream->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $stream->id,
        ]);
    }


    public function test_show_returns_404_for_non_existent_stream(): void
    {
        $fakeUuid = $this->faker->uuid();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/streams/{$fakeUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Stream not found',
            ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/streams', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/streams');

        $response->assertStatus(401);
    }
}