<?php

namespace Lunar\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CollectionIndexer extends ScoutIndexer
{
    /**
     * {@inheritdoc}
     *
     * Typesense PHP client's \Typesense\Collections::__get() returns the internal
     * $collections property when the index name is exactly "collections", breaking
     * Laravel Scout's TypesenseEngine (retrieve() on array).
     */
    public function searchableAs(Model $model): string
    {
        $tablePrefix = config('lunar.database.table_prefix');
        $name = str_replace($tablePrefix, '', $model->getTable());

        if ($name === 'collections') {
            $name = 'lunar_collections';
        }

        return config('scout.prefix').$name;
    }

    public function getSortableFields(): array
    {
        return [
            'created_at',
            'updated_at',
            'name',
        ];
    }

    public function getFilterableFields(): array
    {
        return [
            '__soft_deleted',
            'name',
        ];
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query;
    }

    public function toSearchableArray(Model $model): array
    {
        return array_merge([
            'id' => (string) $model->id,
            'created_at' => (int) $model->created_at->timestamp,
        ], $this->mapSearchableAttributes($model));
    }
}
