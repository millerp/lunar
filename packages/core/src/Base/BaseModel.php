<?php

namespace Lunar\Base;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Traits\HasModelExtending;
use ReflectionClass;

abstract class BaseModel extends Model
{
    use HasModelExtending;

    /**
     * Laravel 13+ resolves custom collections by walking to the parent model; that calls
     * `new` on the parent class. BaseModel is abstract, so we only read #[CollectedBy] on
     * the concrete model and otherwise fall back to the default Eloquent collection.
     *
     * @return class-string<Collection>|null
     */
    public function resolveCollectionFromAttribute()
    {
        $reflectionClass = new ReflectionClass(static::class);
        $attributes = $reflectionClass->getAttributes(CollectedBy::class);

        if (isset($attributes[0]) && isset($attributes[0]->getArguments()[0])) {
            return $attributes[0]->getArguments()[0];
        }

        return null;
    }

    /**
     * Create a new instance of the Model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('lunar.database.table_prefix').$this->getTable());

        if ($connection = config('lunar.database.connection')) {
            $this->setConnection($connection);
        }
    }
}
