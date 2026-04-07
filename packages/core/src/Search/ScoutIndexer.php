<?php

namespace Lunar\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Facades\AttributeManifest;
use Lunar\FieldTypes\ListField;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Search\Interfaces\ScoutIndexerInterface;

class ScoutIndexer implements ScoutIndexerInterface
{
    public function searchableAs(Model $model): string
    {
        $tablePrefix = config('lunar.database.table_prefix');
        $name = str_replace($tablePrefix, '', $model->getTable());

        return config('scout.prefix').$name;
    }

    public function shouldBeSearchable(Model $model): bool
    {
        return true;
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query;
    }

    public function getScoutKey(Model $model): mixed
    {
        return $model->getKey();
    }

    public function getScoutKeyName(Model $model): mixed
    {
        return $model->getKeyName();
    }

    public function getSortableFields(): array
    {
        return [
            'created_at',
            'updated_at',
        ];
    }

    public function getFilterableFields(): array
    {
        return [
            '__soft_deleted',
        ];
    }

    public function toSearchableArray(Model $model): array
    {
        if (! $model->attribute_data) {
            $data = $model->toArray();
        } else {
            $data = $this->mapSearchableAttributes($model);
        }

        return array_merge([
            'id' => (string) $model->id,
        ], $data);
    }

    protected function mapSearchableAttributes(Model $model): array
    {
        $attributes = AttributeManifest::getSearchableAttributes(
            $model->getMorphClass()
        );

        $attributeData = $model->attribute_data;

        if (! $attributeData) {
            return [];
        }

        $data = [];

        foreach ($attributes as $attribute) {
            $attributeValue = $attributeData->get($attribute->handle);

            if ($attributeValue instanceof TranslatedText) {
                foreach ($attributeValue->getValue() as $locale => $text) {
                    $data[$attribute->handle.'_'.$locale] = $text?->getValue();
                }

                continue;
            }

            $isListFieldAttribute = $attributeValue instanceof ListField
                || (isset($attribute->type) && (string) $attribute->type === ListField::class);

            if ($isListFieldAttribute) {
                // Filament KeyValue persiste mapas associativos; Typesense exige JSON array para campos tipados como array.
                // Se não houver instância ListField na coleção, attr()/translateAttribute devolve getValue() cru (pode ser mapa associativo).
                $raw = $attributeValue instanceof ListField
                    ? $attributeValue->getValue()
                    : $model->attr($attribute->handle);

                $data[$attribute->handle] = $this->normalizeListFieldForTypesense(
                    is_array($raw) ? $raw : []
                );

                continue;
            }

            $data[$attribute->handle] = $model->attr($attribute->handle);
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    protected function normalizeListFieldForTypesense(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        if ($raw === []) {
            return [];
        }

        if (array_is_list($raw)) {
            return array_values(array_filter(
                array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $raw),
                static fn (string $s): bool => $s !== '',
            ));
        }

        $tokens = [];

        foreach ($raw as $key => $value) {
            if (is_string($key) && $key !== '') {
                $tokens[] = $key;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $tokens[] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return array_values(array_unique(array_filter($tokens, static fn (string $s): bool => $s !== '')));
    }
}
