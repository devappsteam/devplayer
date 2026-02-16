<?php

namespace App\Modules\IPTV\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\IPTV\Models\IPTV;

class IPTVTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_returns_paginated_iPTVs(): void
    {
        IPTV::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/iPTVs');

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

    public function test_store_creates_new_iPTV(): void
    {
        $data = [
            // Add test data here
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/iPTVs', $data);

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

    public function test_show_returns_specific_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/iPTVs/{$iPTV->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $iPTV->uuid,
                ],
            ]);
    }

    public function test_update_modifies_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();

        $data = [

        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/iPTVs/{$iPTV->uuid}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $iPTV->refresh();


    }

    public function test_destroy_deletes_iPTV(): void
    {
        $iPTV = IPTV::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/iPTVs/{$iPTV->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $iPTV->id,
        ]);
    }


    public function test_show_returns_404_for_non_existent_iPTV(): void
    {
        $fakeUuid = $this->faker->uuid();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/iPTVs/{$fakeUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'IPTV not found',
            ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/iPTVs', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/iPTVs');

        $response->assertStatus(401);
    }
}