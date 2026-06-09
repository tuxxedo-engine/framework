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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface as DatabaseHydratorInterface;
use Tuxxedo\Database\Query\Builder\ExistsBuilderInterface;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Hydrator\Hydrator;
use Tuxxedo\Model\Hydrator\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Reflection\PropertyReflector;

#[DefaultInitializer(
    static function (ContainerInterface $container): ModelsManagerInterface {
        return new ModelsManager(
            connection: $container->resolve(ConnectionManagerInterface::class)->getDefaultConnection(),
            metaData: $container->resolve(MetaDataInterface::class),
            dirtyTracker: $container->resolve(DirtyTrackerInterface::class),
            databaseHydrator: $container->resolve(DatabaseHydratorInterface::class),
        );
    },
)]
class ModelsManager implements ModelsManagerInterface
{
    public readonly HydratorInterface $hydrator;

    /**
     * @var \WeakMap<object, true>
     */
    private \WeakMap $saveInProgress;

    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly MetaDataInterface $metaData,
        public readonly DirtyTrackerInterface $dirtyTracker,
        DatabaseHydratorInterface $databaseHydrator,
        ?HydratorInterface $modelHydrator = null,
    ) {
        $this->hydrator = $modelHydrator ?? new Hydrator($this, $metaData, $databaseHydrator);
        $this->saveInProgress = new \WeakMap();
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function save(
        object $model,
    ): object {
        if (isset($this->saveInProgress[$model])) {
            return $model;
        }

        $this->saveInProgress[$model] = true;

        try {
            $metaData = $this->metaData->getModel($model::class);

            if ($this->isNewModel($model, $metaData)) {
                $target = $metaData->readonly
                    ? $model
                    : clone $model;

                $result = $this->insert($target, $metaData);
            } else {
                $result = $this->update($model, $metaData);
            }

            // @todo Wrap cascade in savepoint for atomicity (depends on cascade caller semantics)
            $this->cascadeRelations($result, $metaData);

            return $result;
        } finally {
            unset($this->saveInProgress[$model]);
        }
    }

    private function isNewModel(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            return PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model) === null;
        }

        foreach ($metaData->key->properties as $property) {
            if (PropertyReflector::createFromObject($model, $property)->getValue($model) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $skipColumns
     * @return array<string, scalar|null>
     */
    private function buildColumnMap(
        object $model,
        ModelMetaDataInterface $metaData,
        array $skipColumns = [],
    ): array {
        $map = [];

        foreach ($metaData->columns as $column) {
            if (\in_array($column->column, $skipColumns, true)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($model, $column->property)->getValue($model);

            if ($value === null && !$column->nullable) {
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $column->property,
                );
            }

            $value = self::dehydrateScalar($value);

            if ($value !== null && !\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $column->property,
                    actualType: \get_debug_type($value),
                );
            }

            $map[$column->column] = $value;
        }

        return $map;
    }

    // @todo This should be moved to the Hydrator handlers
    private static function dehydrateScalar(
        mixed $value,
    ): mixed {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        return $value;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    private function insert(
        object $model,
        ModelMetaDataInterface $metaData,
    ): object {
        $skipColumns = [];

        if (
            $metaData->key instanceof ModelPrimaryKeyInterface &&
            $metaData->key->autoIncrement
        ) {
            $skipColumns[] = $metaData->key->column;
        }

        $columns = $this->buildColumnMap($model, $metaData, $skipColumns);
        $query = $this->connection->insert($metaData->table);

        foreach ($columns as $column => $value) {
            $query->set($column, $value);
        }

        $query->execute();

        $result = $model;

        if (
            $metaData->key instanceof ModelPrimaryKeyInterface &&
            $metaData->key->autoIncrement
        ) {
            $id = $this->connection->lastInsertIdAsInt();

            if ($id !== null) {
                if ($metaData->readonly) {
                    $result = clone (
                        $model,
                        [
                            $metaData->key->property => $id,
                        ],
                    );
                } else {
                    PropertyReflector::createFromObject($model, $metaData->key->property)->setValue($model, $id);
                }
            }
        }

        $this->dirtyTracker->recordSnapshot($result, $metaData);

        return $result;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    private function update(
        object $model,
        ModelMetaDataInterface $metaData,
    ): object {
        $dirty = $this->dirtyTracker->getDirtyColumns($model, $metaData);

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            unset($dirty[$metaData->key->column]);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach ($metaData->key->columns as $column) {
                unset($dirty[$column]);
            }
        }

        if ($dirty === []) {
            return $model;
        }

        $query = $this->connection->update($metaData->table);

        foreach ($metaData->columns as $modelColumn) {
            if (!\array_key_exists($modelColumn->column, $dirty)) {
                continue;
            }

            $value = $dirty[$modelColumn->column];

            if ($value === null && !$modelColumn->nullable) {
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $modelColumn->property,
                );
            }

            if ($value !== null && !\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $modelColumn->property,
                    actualType: \get_debug_type($value),
                );
            }

            $query->set($modelColumn->column, $value);
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);
            $value = self::dehydrateScalar($value);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where($metaData->key->column, $value);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($model, $property)->getValue($model);
                $value = self::dehydrateScalar($value);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: \get_debug_type($value),
                    );
                }

                $query->where($column, $value);
            }
        }

        $query->execute();

        $this->dirtyTracker->recordSnapshot($model, $metaData);

        return $model;
    }

    // @todo DELETE cascade (separate concern, opposite walk order — children first)
    // @todo Optional flag to force materialization during cascade for full graph save
    private function cascadeRelations(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if ($attribute instanceof HasOne || $attribute instanceof BelongsTo) {
                $this->cascadeSingleObjectRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->cascadeCollectionRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                // @todo BelongsToMany pivot writes (depends on collection-mutation API)
                $this->cascadeCollectionRelation($model, $relation);
            }
        }
    }

    private function cascadeSingleObjectRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!\is_object($value)) {
            return;
        }

        if ((new \ReflectionClass($value))->isUninitializedLazyObject($value)) {
            return;
        }

        $saved = $this->save($value);
    }

    private function cascadeCollectionRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        if (!$value->isMaterialized()) {
            return;
        }

        // @todo New untracked children in *-to-many — pending Relation collection-mutation API
        foreach ($value as $item) {
            $saved = $this->save($item);
        }
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        return $query->limit(1)->fetch($class, $this->hydrator);
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetch(
        string $class,
        ?\Closure $criteria = null,
    ): object {
        return $this->findFirst($class, $criteria) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectBuilderInterface $builder) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                $builder->where($metaData->key->column, $id);
            },
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): object {
        return $this->findByIdentifier($class, $id, $criteria) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelCompositeKeyInterface) {
            throw ModelException::fromCantFetchWithoutCompositeKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectBuilderInterface $builder) use ($criteria, $keys): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                foreach ($keys as $column => $value) {
                    $builder->where($column, $value);
                }
            },
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
    ): object {
        return $this->findByCompositeKey($class, $keys, $criteria) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return \Generator<TModel>
     */
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
    ): \Generator {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        yield from $query->fetchAll($class, $this->hydrator);
    }

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function refresh(
        object $model,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->property)->getValue($model);

        if (!\is_int($value) && !\is_string($value)) {
            throw ModelException::fromPropertyValueMustBeIdentifierType(
                modelClass: $metaData->model,
                property: $metaData->key->property,
                actualType: \get_debug_type($value),
            );
        }

        $fresh = $this->findByIdentifier($model::class, $value);

        if ($fresh === null) {
            throw ModelException::fromModelNoLongerExists(
                modelClass: $metaData->model,
            );
        }

        return $fresh;
    }

    /**
     * @param class-string $class
     * @param \Closure(ExistsBuilderInterface $builder): void $criteria
     */
    #[\NoDiscard]
    public function exists(
        string $class,
        \Closure $criteria,
    ): bool {
        $query = $this->connection->exists($this->metaData->getModel($class)->table);

        $criteria($query);

        return $query->exists();
    }

    /**
     * @param class-string $class
     * @param (\Closure(ExistsBuilderInterface $builder): void) $criteria
     */
    #[\NoDiscard]
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): bool {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->exists(
            class: $class,
            criteria: static function (ExistsBuilderInterface $builder) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                $builder->where($metaData->key->column, $id);
            },
        );
    }

    #[\NoDiscard]
    public function delete(
        object $model,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);

        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        $query = $this->connection->delete($metaData->table);

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->property)->getValue($model);
            $value = self::dehydrateScalar($value);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where(
                $metaData->key->column,
                $value,
            );
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($metaData->model, $property)->getValue($model);
                $value = self::dehydrateScalar($value);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: \get_debug_type($value),
                    );
                }

                $query->where($column, $value);
            }
        }

        return $query->execute()->affectedRows > 0;
    }

    #[\NoDiscard]
    public function isRelationLoaded(
        object $model,
        string $property,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);
        $this->findRelation($metaData, $property);

        $reflectionProperty = PropertyReflector::createFromObject($model, $property);

        if (!$reflectionProperty->reflector->isInitialized($model)) {
            return false;
        }

        $value = $reflectionProperty->getValue($model);

        if (!\is_object($value)) {
            return true;
        }

        return !(new \ReflectionClass($value))->isUninitializedLazyObject($value);
    }

    #[\NoDiscard]
    public function relation(
        object $model,
        string $property,
    ): ?object {
        $metaData = $this->metaData->getModel($model::class);
        $this->findRelation($metaData, $property);

        $value = PropertyReflector::createFromObject($model, $property)->getValue($model);

        if (!\is_object($value)) {
            return null;
        }

        $reflection = new \ReflectionClass($value);

        if ($reflection->isUninitializedLazyObject($value)) {
            return $reflection->initializeLazyObject($value);
        }

        return $value;
    }

    public function trackAsExisting(
        object $model,
    ): void {
        $this->dirtyTracker->recordSnapshot(
            $model,
            $this->metaData->getModel($model::class),
        );
    }

    private function findRelation(
        ModelMetaDataInterface $metaData,
        string $property,
    ): ModelRelationInterface {
        foreach ($metaData->relations as $relation) {
            if ($relation->property === $property) {
                return $relation;
            }
        }

        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $property,
        );
    }
}
