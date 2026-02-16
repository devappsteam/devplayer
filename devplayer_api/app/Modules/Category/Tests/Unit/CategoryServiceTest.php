<?php

namespace App\Modules\Category\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Services\CategoryService;
use App\Modules\Category\Repositories\CategoryRepository;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryService $service;
    protected CategoryRepository $repository;

  
    protected function setUp(): void
    {
        parent::setUp();

        $model = new Category();
        $this->repository = new CategoryRepository($model);
        $this->service = new CategoryService($this->repository);
    }

  
    public function test_service_can_retrieve_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $categories = $this->service->all();

        $this->assertCount(3, $categories);
    }

   
    public function test_service_can_paginate_categories(): void
    {
        Category::factory()->count(20)->create();

        $paginated = $this->service->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

  
    public function test_service_can_find_category_by_id(): void
    {
        $category = Category::factory()->create();

        $found = $this->service->findById($category->id);

        $this->assertNotNull($found);
        $this->assertEquals($category->id, $found->id);
    }

  
    public function test_service_can_find_category_by_uuid(): void
    {
        $category = Category::factory()->create();

        $found = $this->service->findByUuid($category->uuid);

        $this->assertNotNull($found);
        $this->assertEquals($category->uuid, $found->uuid);
    }


    public function test_service_can_create_category(): void
    {
        $data = [
            
        ];

        $category = $this->service->create($data);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $category->id,
        ]);
    }


    public function test_service_can_update_category(): void
    {
        $category = Category::factory()->create();

        $data = [
            
        ];

        $updated = $this->service->update($category, $data);

        $this->assertTrue($updated);
        $category->refresh();


    }

    public function test_service_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $deleted = $this->service->delete($category);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('{{TABLE_NAME}}', [
            'id' => $category->id,
        ]);
    }

    public function test_service_can_restore_deleted_category(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $restored = $this->service->restore($category);

        $this->assertTrue($restored);
        $this->assertDatabaseHas('{{TABLE_NAME}}', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_service_returns_null_for_non_existent_category(): void
    {
        $found = $this->service->findById(999999);

        $this->assertNull($found);
    }
}