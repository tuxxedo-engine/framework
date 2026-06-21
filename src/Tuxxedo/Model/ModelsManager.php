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
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Behavior\BeforeDeleteBehaviorInterface;
use Tuxxedo\Model\Behavior\BeforeInsertBehaviorInterface;
use Tuxxedo\Model\Behavior\BeforeUpdateBehaviorInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Behavior\SoftDeleteBehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
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
            container: $container,
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

    /**
     * @var \WeakMap<ColumnInterface, CoercerInterface>
     */
    private \WeakMap $coercerCache;

    /**
     * @var array<class-string<BehaviorInterface>, BehaviorInterface>
     */
    private array $behaviorCache = [];

    public function __construct(
        public readonly ContainerInterface $container,
        public readonly ConnectionInterface $connection,
        public readonly MetaDataInterface $metaData,
        public readonly DirtyTrackerInterface $dirtyTracker,
        DatabaseHydratorInterface $databaseHydrator,
        ?HydratorInterface $modelHydrator = null,
    ) {
        $this->hydrator = $modelHydrator ?? new Hydrator($this, $metaData, $databaseHydrator);
        $this->saveInProgress = new \WeakMap();
        $this->deleteInProgress = new \WeakMap();
        $this->coercerCache = new \WeakMap();
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
        bool $forceMaterialize = false,
    ): object {
        if (isset($this->saveInProgress[$model])) {
            return $model;
        }

        $this->saveInProgress[$model] = true;

        try {
            return $this->connection->nestedTransaction(
                fn (): object => $this->doSave($model, $forceMaterialize),
            );
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
        bool $forceMaterialize,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if ($this->isNewModel($model, $metaData)) {
            $this->dispatchBeforeInsert($model, $metaData);

            $target = $metaData->readonly
                ? $model
                : clone $model;

            $result = $this->insert($target, $metaData);
        } else {
            $this->dispatchBeforeUpdate($model, $metaData);

            $result = $this->update($model, $metaData);
        }

        $this->cascadeSaveRelations($result, $metaData, $forceMaterialize);

        return $result;
    }

    private function dispatchBeforeInsert(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->behaviorsOf(BeforeInsertBehaviorInterface::class) as $property => $behaviorClass) {
            $column = $metaData->columnFor($property);

            if ($column === null) {
                continue;
            }

            $this->getBehaviorFor($behaviorClass)->beforeInsert($model, $column);
        }
    }

    private function dispatchBeforeUpdate(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->behaviorsOf(BeforeUpdateBehaviorInterface::class) as $property => $behaviorClass) {
            $column = $metaData->columnFor($property);

            if ($column === null) {
                continue;
            }

            $this->getBehaviorFor($behaviorClass)->beforeUpdate($model, $column);
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

            if ($value !== null) {
                $coercer = $this->getCoercerFor($column->attribute);

                if ($coercer !== null) {
                    $value = $coercer->dehydrate($value);
                } elseif (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $column->property,
                        actualType: \get_debug_type($value),
                    );
                }
            }

            $map[$column->column] = $value;
        }

        return $map;
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

            $value = $this->dehydrateColumnValue($metaData, $modelColumn->property, $value);

            $query->set($modelColumn->column, $value);
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);
            $value = $this->dehydrateColumnValue($metaData, $metaData->key->property, $value);

            if ($value === null) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: 'null',
                );
            }

            $query->where($metaData->key->column, $value);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($model, $property)->getValue($model);
                $value = $this->dehydrateColumnValue($metaData, $property, $value);

                if ($value === null) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: 'null',
                    );
                }

                $query->where($column, $value);
            }
        }

        $query->execute();

        $this->dirtyTracker->recordSnapshot($model, $metaData);

        return $model;
    }

    private function cascadeSaveRelations(
        object $model,
        ModelMetaDataInterface $metaData,
        bool $forceMaterialize,
    ): void {
        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if ($attribute instanceof HasOneThrough || $attribute instanceof HasManyThrough) {
                $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

                if (
                    $value instanceof RelationInterface &&
                    (
                        $value->pendingAdds !== [] ||
                        $value->pendingRemoves !== []
                    )
                ) {
                    throw ModelException::fromImmutableRelation();
                }

                continue;
            }

            if ($attribute->onSave !== CascadeAction::CASCADE) {
                continue;
            }

            if ($attribute instanceof HasOne || $attribute instanceof BelongsTo) {
                $this->cascadeSaveSingleObjectRelation($model, $relation, $forceMaterialize);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->cascadeSaveCollectionRelation($model, $relation, $forceMaterialize);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->cascadeSaveBelongsToManyRelation($model, $relation, $forceMaterialize);
            }
        }
    }

    private function cascadeSaveSingleObjectRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!\is_object($value)) {
            return;
        }

        $reflection = new \ReflectionClass($value);

        if ($reflection->isUninitializedLazyObject($value)) {
            if (!$forceMaterialize) {
                return;
            }

            $reflection->initializeLazyObject($value);
        }

        (void) $this->save(
            model: $value,
            forceMaterialize: $forceMaterialize,
        );
    }

    private function cascadeSaveCollectionRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof HasMany) {
            return;
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        $hasPending = $value->pendingAdds !== [] || $value->pendingRemoves !== [];

        if (!$value->isMaterialized() && !$hasPending && !$forceMaterialize) {
            return;
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyProperty = $this->resolveLocalKeyProperty($parentMetaData, $relation, $attribute->localKey);
        $localKeyValue = PropertyReflector::createFromObject($model, $localKeyProperty)->getValue($model);
        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
        $foreignKeyProperty = $this->findPropertyForColumn(
            metaData: $relatedMetaData,
            relation: $relation,
            columnName: $attribute->foreignKey,
            keyKind: 'foreignKey',
        );

        foreach ($value as $item) {
            PropertyReflector::createFromObject($item, $foreignKeyProperty)->setValue($item, $localKeyValue);

            (void) $this->save(
                model: $item,
                forceMaterialize: $forceMaterialize,
            );
        }

        if ($attribute->removeOrphan) {
            foreach ($value->pendingRemoves as $item) {
                (void) $this->delete($item);
            }
        }

        $value->clearPending();
    }

    private function cascadeSaveBelongsToManyRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof BelongsToMany) {
            return;
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        $hasPending = $value->pendingAdds !== [] || $value->pendingRemoves !== [];

        if (!$value->isMaterialized() && !$hasPending && !$forceMaterialize) {
            return;
        }

        foreach ($value as $item) {
            (void) $this->save(
                model: $item,
                forceMaterialize: $forceMaterialize,
            );
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
        $value = $this->dehydrateColumnValue($metaData, $metaData->key->property, $value);

        if ($value === null) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $metaData->key->property,
                actualType: 'null',
            );
        }

        return $value;
    }

    /**
     * @throws ModelException
     */
    private function dehydrateColumnValue(
        ModelMetaDataInterface $metaData,
        string $property,
        mixed $value,
    ): string|int|float|bool|null {
        if ($value === null) {
            return null;
        }

        foreach ($metaData->columns as $column) {
            if ($column->property === $property) {
                $coercer = $this->getCoercerFor($column->attribute);

                if ($coercer !== null) {
                    return $coercer->dehydrate($value);
                }

                break;
            }
        }

        if (!\is_scalar($value)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $property,
                actualType: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function getCoercerFor(
        ColumnInterface $attribute,
    ): ?CoercerInterface {
        if ($attribute->coercer === null) {
            return null;
        }

        if (isset($this->coercerCache[$attribute])) {
            return $this->coercerCache[$attribute];
        }

        /** @var CoercerInterface $instance */
        $instance = $this->container->resolve(
            $attribute->coercer,
            $attribute->coercerArguments,
        );

        return $this->coercerCache[$attribute] = $instance;
    }

    /**
     * @template TBehavior of BehaviorInterface
     *
     * @param class-string<TBehavior> $behaviorClass
     * @return TBehavior
     */
    public function getBehaviorFor(
        string $behaviorClass,
    ): BehaviorInterface {
        if (isset($this->behaviorCache[$behaviorClass])) {
            /** @var TBehavior */
            return $this->behaviorCache[$behaviorClass];
        }

        /** @var TBehavior $instance */
        $instance = $this->container->resolve($behaviorClass);

        $this->behaviorCache[$behaviorClass] = $instance;

        return $instance;
    }

    private function cascadeDeleteRelations(
        object $model,
        ModelMetaDataInterface $metaData,
        bool $force,
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
                $this->cascadeDeleteSingleObjectRelation($model, $relation, $force);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->cascadeDeleteCollectionRelation($model, $relation, $force);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->cascadeDeleteBelongsToManyPivot($model, $relation);

                continue;
            }
        }
    }

    private function cascadeDeleteSingleObjectRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $force,
    ): void {
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!\is_object($value)) {
            return;
        }

        if ($force) {
            (void) $this->forceDelete($value);

            return;
        }

        (void) $this->delete($value);
    }

    private function cascadeDeleteCollectionRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $force,
    ): void {
        $attribute = $relation->attribute;

        if ($attribute instanceof HasMany && $attribute->bulkDelete) {
            $this->cascadeBulkDeleteRelation($model, $relation, $attribute);

            return;
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        foreach ($value as $item) {
            if ($force) {
                (void) $this->forceDelete($item);

                continue;
            }

            (void) $this->delete($item);
        }
    }

    private function cascadeBulkDeleteRelation(
        object $model,
        ModelRelationInterface $relation,
        HasMany $attribute,
    ): void {
        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyProperty = $this->resolveLocalKeyProperty($parentMetaData, $relation, $attribute->localKey);
        $localKeyValue = PropertyReflector::createFromObject($model, $localKeyProperty)->getValue($model);

        if ($localKeyValue === null) {
            return;
        }

        if (!\is_scalar($localKeyValue)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $parentMetaData->model,
                property: $localKeyProperty,
                actualType: \get_debug_type($localKeyValue),
            );
        }

        $childMetaData = $this->metaData->getModel($relation->relatedClass);

        $this->connection
            ->delete($childMetaData->table)
            ->where($attribute->foreignKey, $localKeyValue)
            ->execute();
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
        $localKeyValue = $this->dehydrateColumnValue($parentMetaData, $localKeyProperty, $localKeyValue);

        if ($localKeyValue === null) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $parentMetaData->model,
                property: $localKeyProperty,
                actualType: 'null',
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

        return $this->findPropertyForColumn(
            metaData: $metaData,
            relation: $relation,
            columnName: $localKey,
            keyKind: 'localKey',
        );
    }

    private function findPropertyForColumn(
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        string $columnName,
        string $keyKind,
    ): string {
        foreach ($metaData->columns as $column) {
            if ($column->column === $columnName) {
                return $column->property;
            }
        }

        throw ModelException::fromRelationKeyReferencesUnknownColumn(
            modelClass: $metaData->model,
            property: $relation->property,
            keyKind: $keyKind,
            keyValue: $columnName,
            referencedClass: $metaData->model,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        if (!$includeDeleted) {
            $this->applySoftDeleteFilter($query, $metaData);
        }

        $result = $query->limit(1)->fetch($class, $this->hydrator);

        if ($result === null) {
            return null;
        }

        if ($with !== null && \sizeof($with) > 0) {
            $this->hydrator->eagerLoad(
                parents: [
                    $result,
                ],
                with: $with,
            );
        }

        return $result;
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetch(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        return $this->findFirst($class, $criteria, $includeDeleted, $with) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectStatementInterface $statement) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($statement);
                }

                $statement->where($metaData->key->column, $id);
            },
            includeDeleted: $includeDeleted,
            with: $with,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        return $this->findByIdentifier($class, $id, $criteria, $includeDeleted, $with) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelCompositeKeyInterface) {
            throw ModelException::fromCantFetchWithoutCompositeKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectStatementInterface $statement) use ($criteria, $keys): void {
                if ($criteria !== null) {
                    $criteria($statement);
                }

                foreach ($keys as $column => $value) {
                    $statement->where($column, $value);
                }
            },
            includeDeleted: $includeDeleted,
            with: $with,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        return $this->findByCompositeKey($class, $keys, $criteria, $includeDeleted, $with) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, ?\Closure(Relation<object>): Relation<object>>|null $with
     * @return \Generator<int, TModel>
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): \Generator {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        if (!$includeDeleted) {
            $this->applySoftDeleteFilter($query, $metaData);
        }

        if ($with === null || \sizeof($with) === 0) {
            yield from $query->fetchAll($class, $this->hydrator);

            return;
        }

        $parents = \iterator_to_array(
            $query->fetchAll($class, $this->hydrator),
            preserve_keys: false,
        );

        if (\sizeof($parents) > 0) {
            $this->hydrator->eagerLoad(
                parents: $parents,
                with: $with,
            );
        }

        yield from $parents;
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

        $fresh = $this->findByIdentifier($model::class, $value, includeDeleted: true);

        if ($fresh === null) {
            throw ModelException::fromModelNoLongerExists(
                modelClass: $metaData->model,
            );
        }

        return $fresh;
    }

    /**
     * @param class-string $class
     * @param \Closure(ExistsStatementInterface $statement): void $criteria
     */
    #[\NoDiscard]
    public function exists(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): bool {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->exists($metaData->table);

        $criteria($query);

        if (!$includeDeleted) {
            $this->applySoftDeleteFilter($query, $metaData);
        }

        return $query->exists();
    }

    /**
     * @param class-string $class
     * @param (\Closure(ExistsStatementInterface $statement): void) $criteria
     */
    #[\NoDiscard]
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): bool {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->exists(
            class: $class,
            criteria: static function (ExistsStatementInterface $statement) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($statement);
                }

                $statement->where($metaData->key->column, $id);
            },
            includeDeleted: $includeDeleted,
        );
    }

    /**
     * @param class-string $class
     * @param \Closure(CountStatementInterface $statement): void $criteria
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function count(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): int {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->count($metaData->table);

        $criteria($query);

        if (!$includeDeleted) {
            $this->applySoftDeleteFilter($query, $metaData);
        }

        return $query->count();
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
            return $this->connection->nestedTransaction(
                fn (): bool => $this->doDelete($model),
            );
        } finally {
            unset($this->deleteInProgress[$model]);
        }
    }

    #[\NoDiscard]
    public function forceDelete(
        object $model,
    ): bool {
        if (isset($this->deleteInProgress[$model])) {
            return true;
        }

        $this->deleteInProgress[$model] = true;

        try {
            return $this->connection->nestedTransaction(
                fn (): bool => $this->doForceDelete($model),
            );
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

        $this->dispatchBeforeDelete($model, $metaData);

        $this->cascadeDeleteRelations($model, $metaData, force: false);

        if ($metaData->behaviorsOf(SoftDeleteBehaviorInterface::class) !== []) {
            return $this->softDelete($model, $metaData);
        }

        return $this->hardDelete($model, $metaData);
    }

    private function doForceDelete(
        object $model,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);

        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        $this->dispatchBeforeDelete($model, $metaData);

        $this->cascadeDeleteRelations($model, $metaData, force: true);

        return $this->hardDelete($model, $metaData);
    }

    private function dispatchBeforeDelete(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->behaviorsOf(BeforeDeleteBehaviorInterface::class) as $property => $behaviorClass) {
            $column = $metaData->columnFor($property);

            if ($column === null) {
                continue;
            }

            $this->getBehaviorFor($behaviorClass)->beforeDelete($model, $column);
        }
    }

    private function hardDelete(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        $query = $this->connection->delete($metaData->table);

        $this->applyKeyWhere($query, $model, $metaData);

        return $query->execute()->affectedRows > 0;
    }

    private function softDelete(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        $query = $this->connection->update($metaData->table);

        foreach ($metaData->behaviorsOf(BeforeDeleteBehaviorInterface::class) as $property => $behaviorClass) {
            $column = $metaData->columnFor($property);

            if ($column === null) {
                continue;
            }

            $value = PropertyReflector::createFromObject($model, $property)->getValue($model);
            $value = $this->dehydrateColumnValue($metaData, $property, $value);

            $query->set($column->column, $value);
        }

        $this->applyKeyWhere($query, $model, $metaData);

        return $query->execute()->affectedRows > 0;
    }

    private function applyKeyWhere(
        WhereStatementInterface $query,
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->property)->getValue($model);
            $value = $this->dehydrateColumnValue($metaData, $metaData->key->property, $value);

            if ($value === null) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: 'null',
                );
            }

            $query->where(
                $metaData->key->column,
                $value,
            );

            return;
        }

        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($metaData->model, $property)->getValue($model);
                $value = $this->dehydrateColumnValue($metaData, $property, $value);

                if ($value === null) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: 'null',
                    );
                }

                $query->where($column, $value);
            }
        }
    }

    private function applySoftDeleteFilter(
        WhereStatementInterface $query,
        ModelMetaDataInterface $metaData,
    ): void {
        $softDeleteBehaviors = $metaData->behaviorsOf(SoftDeleteBehaviorInterface::class);

        if ($softDeleteBehaviors === []) {
            return;
        }

        $property = \array_key_first($softDeleteBehaviors);
        $column = $metaData->columnFor($property);

        if ($column === null) {
            return;
        }

        $query->whereNull($column->column);
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
