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

    /**
     * @var \WeakMap<object, true>
     */
    private \WeakMap $deleteInProgress;

    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly MetaDataInterface $metaData,
        public readonly DirtyTrackerInterface $dirtyTracker,
        DatabaseHydratorInterface $databaseHydrator,
        ?HydratorInterface $modelHydrator = null,
    ) {
        $this->hydrator = $modelHydrator ?? new Hydrator($this, $metaData, $databaseHydrator);
        $this->saveInProgress = new \WeakMap();
        $this->deleteInProgress = new \WeakMap();
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
            $result = $model;

            $this->connection->nestedTransaction(
                function () use ($model, &$result): void {
                    $result = $this->doSave($model);
                },
            );

            return $result;
        } finally {
            unset($this->saveInProgress[$model]);
        }
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    private function doSave(
        object $model,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if ($this->isNewModel($model, $metaData)) {
            $target = $metaData->readonly
                ? $model
                : clone $model;

            $result = $this->insert($target, $metaData);
        } else {
            $result = $this->update($model, $metaData);
        }

        $this->cascadeSaveRelations($result, $metaData);

        return $result;
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

    // @todo Optional flag to force materialization during save cascade for full graph save
    private function cascadeSaveRelations(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            if ($relation->attribute->onSave !== CascadeAction::CASCADE) {
                continue;
            }

            $attribute = $relation->attribute;

            if ($attribute instanceof HasOne || $attribute instanceof BelongsTo) {
                $this->cascadeSaveSingleObjectRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->cascadeSaveCollectionRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->cascadeSaveBelongsToManyRelation($model, $relation);
            }
        }
    }

    private function cascadeSaveSingleObjectRelation(
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

        (void) $this->save($value);
    }

    private function cascadeSaveCollectionRelation(
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
            (void) $this->save($item);
        }
    }

    private function cascadeSaveBelongsToManyRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof BelongsToMany) {
            return;
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        foreach ($value as $item) {
            (void) $this->save($item);
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyValue = $this->resolveOwnKeyValue($model, $parentMetaData);

        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);

        foreach ($value->pendingRemoves as $item) {
            $foreignKeyValue = $this->resolveOwnKeyValue($item, $relatedMetaData);

            $this->connection
                ->delete($attribute->table)
                ->where($attribute->localKey, $localKeyValue)
                ->where($attribute->foreignKey, $foreignKeyValue)
                ->execute();
        }

        foreach ($value->pendingAdds as $item) {
            $foreignKeyValue = $this->resolveOwnKeyValue($item, $relatedMetaData);

            $this->connection
                ->insert($attribute->table)
                ->set($attribute->localKey, $localKeyValue)
                ->set($attribute->foreignKey, $foreignKeyValue)
                ->execute();
        }

        $value->clearPending();
    }

    private function resolveOwnKeyValue(
        object $model,
        ModelMetaDataInterface $metaData,
    ): string|int|float|bool {
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        $value = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);
        $value = self::dehydrateScalar($value);

        if (!\is_scalar($value)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $metaData->key->property,
                actualType: \get_debug_type($value),
            );
        }

        return $value;
    }

    private function cascadeDeleteRelations(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            $action = $relation->attribute->onDelete;

            if ($action === CascadeAction::NO_ACTION) {
                continue;
            }

            if ($action === CascadeAction::RESTRICT) {
                $this->cascadeDeleteRestrictRelation($model, $relation);

                continue;
            }

            if ($action === CascadeAction::SET_NULL) {
                $this->cascadeDeleteSetNullRelation($model, $relation);

                continue;
            }

            $attribute = $relation->attribute;

            if ($attribute instanceof HasOne || $attribute instanceof BelongsTo) {
                $this->cascadeDeleteSingleObjectRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->cascadeDeleteCollectionRelation($model, $relation);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->cascadeDeleteBelongsToManyPivot($model, $relation);
            }
        }
    }

    private function cascadeDeleteSingleObjectRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!\is_object($value)) {
            return;
        }

        (void) $this->delete($value);
    }

    private function cascadeDeleteCollectionRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        foreach ($value as $item) {
            (void) $this->delete($item);
        }
    }

    private function cascadeDeleteBelongsToManyPivot(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof BelongsToMany) {
            return;
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyValue = $this->resolveOwnKeyValue($model, $parentMetaData);

        $this->connection
            ->delete($attribute->table)
            ->where($attribute->localKey, $localKeyValue)
            ->execute();
    }

    private function cascadeDeleteRestrictRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if ($attribute instanceof HasOne) {
            if ($value === null) {
                return;
            }

            throw ModelException::fromRestrictedRelation(
                modelClass: $model::class,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        if ($attribute instanceof HasMany) {
            if (!$value instanceof RelationInterface) {
                return;
            }

            if ($value->totalCount === 0) {
                return;
            }

            throw ModelException::fromRestrictedRelation(
                modelClass: $model::class,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }
    }

    private function cascadeDeleteSetNullRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (
            !$attribute instanceof HasOne &&
            !$attribute instanceof HasMany
        ) {
            return;
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyProperty = $this->resolveLocalKeyProperty($parentMetaData, $relation, $attribute->localKey);
        $localKeyValue = PropertyReflector::createFromObject($model, $localKeyProperty)->getValue($model);
        $localKeyValue = self::dehydrateScalar($localKeyValue);

        if (!\is_scalar($localKeyValue)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $parentMetaData->model,
                property: $localKeyProperty,
                actualType: \get_debug_type($localKeyValue),
            );
        }

        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);

        $this->connection
            ->update($relatedMetaData->table)
            ->set($attribute->foreignKey, null)
            ->where($attribute->foreignKey, $localKeyValue)
            ->execute();
    }

    private function resolveLocalKeyProperty(
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?string $localKey,
    ): string {
        if ($localKey === null) {
            if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $metaData->model,
                );
            }

            return $metaData->key->property;
        }

        foreach ($metaData->columns as $column) {
            if ($column->column === $localKey) {
                return $column->property;
            }
        }

        throw ModelException::fromRelationKeyReferencesUnknownColumn(
            modelClass: $metaData->model,
            property: $relation->property,
            keyKind: 'localKey',
            keyValue: $localKey,
            referencedClass: $metaData->model,
        );
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
     * @return \Generator<int, TModel>
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
        if (isset($this->deleteInProgress[$model])) {
            return true;
        }

        $this->deleteInProgress[$model] = true;

        try {
            $result = false;

            $this->connection->nestedTransaction(
                function () use ($model, &$result): void {
                    $result = $this->doDelete($model);
                },
            );

            return $result;
        } finally {
            unset($this->deleteInProgress[$model]);
        }
    }

    private function doDelete(
        object $model,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);

        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        $this->cascadeDeleteRelations($model, $metaData);

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
