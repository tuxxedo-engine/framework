<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Model;

use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Relation\RelationKeyTupleHasher;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Reflection\PropertyReflector;

class RelationPivot implements RelationPivotInterface
{
    /**
     * @param class-string $relatedClass
     * @param class-string|null $pivotClass
     * @param non-empty-list<string> $pivotSourceColumns
     * @param non-empty-list<string> $pivotTargetColumns
     * @param non-empty-list<string|int|float|bool>|null $parentValues
     * @param array<string, object>|null $pivots
     */
    public function __construct(
        private readonly ModelsManagerInterface $manager,
        public readonly string $relatedClass,
        public readonly ?string $pivotClass,
        public readonly string $table,
        public readonly array $pivotSourceColumns,
        public readonly array $pivotTargetColumns,
        public readonly ?array $parentValues,
        public readonly ?string $typeColumn = null,
        public readonly ?string $typeValue = null,
        private ?array $pivots = null,
    ) {
    }

    /**
     * @param non-empty-list<string|int|float|bool>|null $parentValues
     * @param array<string, object>|null $pivots
     *
     * @throws ModelException
     */
    public static function createFromRelation(
        ModelsManagerInterface $manager,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        ?array $parentValues,
        ?array $pivots = null,
    ): self {
        $attribute = $relation->attribute;
        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if (
            (!$attribute instanceof BelongsToMany && !$attribute instanceof MorphToMany) ||
            $pivotSourceColumns === null ||
            $pivotTargetColumns === null
        ) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $typeColumn = null;
        $typeValue = null;

        if ($attribute instanceof MorphToMany) {
            $typeColumn = $attribute->typeColumn;
            $typeValue = MorphTypeResolver::encode(
                class: $parentMetaData->model,
                typeMap: $attribute->typeMap,
            );
        }

        return new self(
            manager: $manager,
            relatedClass: $relation->relatedClass,
            pivotClass: $attribute->pivot,
            table: $attribute->table,
            pivotSourceColumns: $pivotSourceColumns,
            pivotTargetColumns: $pivotTargetColumns,
            parentValues: $parentValues,
            typeColumn: $typeColumn,
            typeValue: $typeValue,
            pivots: $pivots,
        );
    }

    public function keyOf(
        object|array|int|string $item,
    ): ?array {
        $metaData = $this->manager->metaData->getModel($this->relatedClass);
        $properties = $this->keyProperties($metaData);
        $columns = \array_keys($properties);

        if ($item instanceof \BackedEnum) {
            $item = $item->value;
        }

        if (\is_object($item)) {
            if (!$item instanceof $this->relatedClass) {
                throw ModelException::fromInvalidRelationKey(
                    relatedClass: $this->relatedClass,
                    expected: \sprintf('an instance of "%s"', $this->relatedClass),
                    actual: \get_debug_type($item),
                );
            }

            $key = [];

            foreach ($properties as $column => $property) {
                $value = $this->dehydrate(
                    metaData: $metaData,
                    property: $property,
                    value: PropertyReflector::createFromObject($item, $property)->getValue($item),
                );

                if ($value === null) {
                    return null;
                }

                $key[$column] = $value;
            }

            return $key;
        }

        if (\is_array($item)) {
            if (\sizeof($columns) === 1 || \sizeof($item) !== \sizeof($columns)) {
                throw $this->invalidKey($columns, $item);
            }

            $key = [];

            foreach ($columns as $column) {
                $value = $item[$column] ?? null;

                if (!\is_int($value) && !\is_string($value)) {
                    throw $this->invalidKey($columns, $item);
                }

                $key[$column] = $value;
            }

            return $key;
        }

        if (\sizeof($columns) !== 1) {
            throw $this->invalidKey($columns, $item);
        }

        return [
            $columns[0] => $item,
        ];
    }

    public function hash(
        array $key,
    ): string {
        return RelationKeyTupleHasher::hash(\array_values($key));
    }

    public function resolve(
        array $keys,
    ): array {
        $columns = \array_keys($this->keyProperties($this->manager->metaData->getModel($this->relatedClass)));
        $resolved = [];

        $targets = $this->manager->findAll(
            $this->relatedClass,
            static function (SelectStatementInterface $statement) use ($columns, $keys): void {
                if (\sizeof($columns) === 1) {
                    $statement->whereIn(
                        $columns[0],
                        \array_map(
                            static fn (array $key): string|int|float|bool => $key[$columns[0]],
                            $keys,
                        ),
                    );

                    return;
                }

                $isFirst = true;

                foreach ($keys as $key) {
                    $group = static function (WhereStatementInterface $inner) use ($key): void {
                        foreach ($key as $column => $value) {
                            $inner->where($column, $value);
                        }
                    };

                    if ($isFirst) {
                        $statement->whereGroup($group);
                        $isFirst = false;
                    } else {
                        $statement->orWhereGroup($group);
                    }
                }
            },
        );

        foreach ($targets as $target) {
            $key = $this->keyOf($target);

            if ($key !== null) {
                $resolved[$this->hash($key)] = $target;
            }
        }

        return $resolved;
    }

    public function pivots(): array
    {
        if ($this->pivots !== null) {
            return $this->pivots;
        }

        $pivotClass = $this->pivotClass ?? throw ModelException::fromRelationWithoutPivotModel(
            relatedClass: $this->relatedClass,
        );

        $pivots = [];

        if ($this->parentValues !== null) {
            $found = $this->manager->findAll(
                $pivotClass,
                function (SelectStatementInterface $statement): void {
                    $this->constrain($statement);
                },
            );

            foreach ($found as $pivot) {
                $key = $this->pivotTargetKey($pivot);

                if ($key !== null) {
                    $pivots[$this->hash($key)] = $pivot;
                }
            }
        }

        return $this->pivots = $pivots;
    }

    public function existing(): array
    {
        $keys = [];

        if ($this->pivotClass !== null) {
            foreach ($this->pivots() as $hash => $pivot) {
                $key = $this->pivotTargetKey($pivot);

                if ($key !== null) {
                    $keys[$hash] = $key;
                }
            }

            return $keys;
        }

        $columns = \array_keys($this->keyProperties($this->manager->metaData->getModel($this->relatedClass)));
        $statement = $this->manager->connection
            ->select($this->table)
            ->select(...$this->pivotTargetColumns);

        $this->constrain($statement);

        foreach ($statement->execute() as $row) {
            $key = [];

            foreach ($this->pivotTargetColumns as $index => $pivotTargetColumn) {
                $value = $row->properties[$pivotTargetColumn] ?? null;

                if (!\is_int($value) && !\is_string($value)) {
                    continue 2; // @codeCoverageIgnore
                }

                $key[$columns[$index]] = $value;
            }

            /** @var non-empty-array<string, int|string> $key */
            $keys[$this->hash($key)] = $key;
        }

        return $keys;
    }

    public function isLoaded(): bool
    {
        return $this->pivots !== null;
    }

    public function reset(): void
    {
        $this->pivots = null;
    }

    public function validatePivotValues(
        array $values,
    ): void {
        $pivotClass = $this->pivotClass ?? throw ModelException::fromRelationWithoutPivotModel(
            relatedClass: $this->relatedClass,
        );

        $metaData = $this->manager->metaData->getModel($pivotClass);
        $reserved = [
            ...$this->pivotSourceColumns,
            ...$this->pivotTargetColumns,
            ...\array_keys($this->keyProperties($metaData)),
        ];

        if ($this->typeColumn !== null) {
            $reserved[] = $this->typeColumn;
        }

        foreach ($values as $property => $value) {
            $column = $metaData->columnFor($property);

            if ($column === null || \in_array($column->column, $reserved, true)) {
                throw ModelException::fromInvalidPivotProperty(
                    pivotClass: $pivotClass,
                    property: $property,
                );
            }
        }
    }

    public function newPivot(
        array $key,
        array $values,
    ): object {
        $pivotClass = $this->pivotClass ?? throw ModelException::fromRelationWithoutPivotModel(
            relatedClass: $this->relatedClass,
        );

        $metaData = $this->manager->metaData->getModel($pivotClass);
        $pivot = new $pivotClass();

        foreach ($this->pivotSourceColumns as $index => $pivotSourceColumn) {
            $this->assignColumn($metaData, $pivot, $pivotSourceColumn, $this->parentValues[$index] ?? null);
        }

        if ($this->typeColumn !== null) {
            $this->assignColumn($metaData, $pivot, $this->typeColumn, $this->typeValue);
        }

        $targetValues = \array_values($key);

        foreach ($this->pivotTargetColumns as $index => $pivotTargetColumn) {
            $this->assignColumn($metaData, $pivot, $pivotTargetColumn, $targetValues[$index]);
        }

        $this->fill($pivot, $values);

        return $pivot;
    }

    public function fill(
        object $pivot,
        array $values,
    ): void {
        foreach ($values as $property => $value) {
            PropertyReflector::createFromObject($pivot, $property)->setValue($pivot, $value);
        }
    }

    /**
     * @return non-empty-array<string, string>
     *
     * @throws ModelException
     */
    private function keyProperties(
        ModelMetaDataInterface $metaData,
    ): array {
        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            return [
                $metaData->key->column => $metaData->key->property,
            ];
        }

        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            /** @var non-empty-array<string, string> $properties */
            $properties = \array_combine(
                \array_values($metaData->key->columns),
                \array_values($metaData->key->properties),
            );

            return $properties;
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromCantFetchWithoutPrimaryKey(
            modelClass: $metaData->model,
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return non-empty-array<string, string|int|float|bool>|null
     *
     * @throws ModelException
     */
    private function pivotTargetKey(
        object $pivot,
    ): ?array {
        $metaData = $this->manager->metaData->getModel($pivot::class);
        $columns = \array_keys($this->keyProperties($this->manager->metaData->getModel($this->relatedClass)));
        $key = [];

        foreach ($this->pivotTargetColumns as $index => $pivotTargetColumn) {
            foreach ($metaData->columns as $column) {
                if ($column->column !== $pivotTargetColumn) {
                    continue;
                }

                $value = $this->dehydrate(
                    metaData: $metaData,
                    property: $column->property,
                    value: PropertyReflector::createFromObject($pivot, $column->property)->getValue($pivot),
                );

                if ($value === null) {
                    return null; // @codeCoverageIgnore
                }

                $key[$columns[$index]] = $value;
            }
        }

        if ($key === []) {
            return null; // @codeCoverageIgnore
        }

        return $key;
    }

    private function constrain(
        WhereStatementInterface $statement,
    ): void {
        foreach ($this->pivotSourceColumns as $index => $pivotSourceColumn) {
            $statement->where($pivotSourceColumn, $this->parentValues[$index] ?? null);
        }

        if ($this->typeColumn !== null) {
            $statement->where($this->typeColumn, $this->typeValue);
        }
    }

    private function dehydrate(
        ModelMetaDataInterface $metaData,
        string $property,
        mixed $value,
    ): string|int|float|bool|null {
        if ($value === null) {
            return null;
        }

        $column = $metaData->columnFor($property);

        if ($column !== null) {
            $coercer = $this->manager->getCoercerFor($column->attribute);

            if ($coercer !== null) {
                return $coercer->dehydrate($value);
            }
        }

        if (!\is_scalar($value)) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $property,
                actualType: \get_debug_type($value),
            );
            // @codeCoverageIgnoreEnd
        }

        return $value;
    }

    private function assignColumn(
        ModelMetaDataInterface $metaData,
        object $pivot,
        string $columnName,
        string|int|float|bool|null $value,
    ): void {
        foreach ($metaData->columns as $column) {
            if ($column->column !== $columnName) {
                continue;
            }

            $coercer = $this->manager->getCoercerFor($column->attribute);

            PropertyReflector::createFromObject($pivot, $column->property)->setValue(
                $pivot,
                $coercer !== null && $value !== null
                    ? $coercer->hydrate($value)
                    : $value,
            );

            return;
        }
    }

    /**
     * @param list<string> $columns
     * @param array<mixed>|int|string $item
     */
    private function invalidKey(
        array $columns,
        array|int|string $item,
    ): ModelException {
        return ModelException::fromInvalidRelationKey(
            relatedClass: $this->relatedClass,
            expected: \sizeof($columns) === 1
                ? 'a model or a single key value'
                : \sprintf('a model or an array with the key columns "%s"', \join('", "', $columns)),
            actual: \get_debug_type($item),
        );
    }
}
