<?php

namespace App\Core\Services;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    /**
     * Repository instance
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Get all records
     *
     * @param array $relations
     * @return Collection
     */
    public function all(array $relations = []): Collection
    {
        return $this->repository->all(relations: $relations);
    }

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, relations: $relations);
    }

    /**
     * Find by ID
     *
     * @param int $id
     * @param array $relations
     * @return Model|null
     */
    public function findById(int $id, array $relations = []): ?Model
    {
        return $this->repository->findById($id, relations: $relations);
    }

    /**
     * Find by UUID
     *
     * @param string $uuid
     * @param array $relations
     * @return Model|null
     */
    public function findByUuid(string $uuid, array $relations = []): ?Model
    {
        return $this->repository->findByUuid($uuid, relations: $relations);
    }

    /**
     * Find by column
     *
     * @param string $column
     * @param mixed $value
     * @param array $relations
     * @return Model|null
     */
    public function findBy(string $column, mixed $value, array $relations = []): ?Model
    {
        return $this->repository->findBy($column, $value, relations: $relations);
    }

    /**
     * Create new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * Update record
     *
     * @param Model $model
     * @param array $data
     * @return bool
     */
    public function update(Model $model, array $data): bool
    {
        return $this->repository->update($model, $data);
    }

    /**
     * Delete record (soft delete)
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        return $this->repository->delete($model);
    }

    /**
     * Restore deleted record
     *
     * @param Model $model
     * @return bool
     */
    public function restore(Model $model): bool
    {
        return $this->repository->restore($model);
    }

    /**
     * Force delete record
     *
     * @param Model $model
     * @return bool
     */
    public function forceDelete(Model $model): bool
    {
        return $this->repository->forceDelete($model);
    }

    /**
     * Check if record exists
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function exists(string $column, mixed $value): bool
    {
        return $this->repository->exists($column, $value);
    }

    /**
     * Count records
     *
     * @return int
     */
    public function count(): int
    {
        return $this->repository->count();
    }
}
