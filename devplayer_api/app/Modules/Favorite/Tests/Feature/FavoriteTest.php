<?php

namespace App\Modules\Favorite\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Favorite\Models\Favorite;

class FavoriteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_returns_paginated_favorites(): void
    {
        Favorite::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/favorites');

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

    public function test_store_creates_new_favorite(): void
    {
        $data = [
            // Add test data here
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/favorites', $data);

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

    public function test_show_returns_specific_favorite(): void
    {
        $favorite = Favorite::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/favorites/{$favorite->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $favorite->uuid,
                ],
            ]);
    }

    public function test_update_modifies_favorite(): void
    {
        $favorite = Favorite::factory()->create();

        $data = [

        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/favorites/{$favorite->uuid}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $favorite->refresh();


    }

    public function test_destroy_deletes_favorite(): void
    {
        $favorite = Favorite::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/favorites/{$favorite->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $favorite->id,
        ]);
    }


    public function test_show_returns_404_for_non_existent_favorite(): void
    {
        $fakeUuid = $this->faker->uuid();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/favorites/{$fakeUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Favorite not found',
            ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/favorites', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/favorites');

        $response->assertStatus(401);
    }
}