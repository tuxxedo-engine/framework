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
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Aggregate\AggregateEntityCollector;
use Tuxxedo\Model\Aggregate\AggregateSaveOrder;
use Tuxxedo\Model\Aggregate\AggregateValidator;
use Tuxxedo\Model\Aggregate\CollectedEntity;
use Tuxxedo\Model\Aggregate\RelationLoadState;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
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
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): ModelsManagerInterface {
        return new ModelsManager(
            container: $container,
            connection: $container->resolve(ConnectionManagerInterface::class)->getDefaultConnection(),
            metaData: $container->resolve(MetaDataInterface::class),
            dirtyTracker: $container->resolve(DirtyTrackerInterface::class),
            databaseHydrator: $container->resolve(DatabaseHydratorInterface::class),
            validator: $container->resolve(ValidatorInterface::class),
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
        public readonly ValidatorInterface $validator,
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
     *
     * @throws ModelException
     * @throws ValidationException
     */
    #[\NoDiscard]
    public function save(
        object $model,
        bool $forceMaterialize = false,
        bool $skipValidation = false,
        ValidationScope $scope = ValidationScope::SELF,
        bool $skipCascade = false,
    ): object {
        if ($scope === ValidationScope::AGGREGATE) {
            return $this->saveAggregate($model, $forceMaterialize);
        }

        if (isset($this->saveInProgress[$model])) {
            return $model; // @codeCoverageIgnore
        }

        if (!$skipValidation) {
            $this->validator->validateOrThrow($model);
        }

        $this->saveInProgress[$model] = true;

        try {
            return $this->connection->nestedTransaction(
                fn (): object => $this->doSave($model, $forceMaterialize, $skipCascade),
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
    private function saveAggregate(
        object $model,
        bool $forceMaterialize,
    ): object {
        $collector = new AggregateEntityCollector($this->metaData);
        $aggregateValidator = new AggregateValidator($this->validator);
        $order = new AggregateSaveOrder();
        $entities = $collector->collect($model);

        $this->guardAggregateConnections($entities);
        $aggregateValidator->validateOrThrow($entities);

        $sorted = $order->sort($entities);

        /** @var TModel $result */
        $result = $this->connection->nestedTransaction(
            function () use ($sorted, $model, $forceMaterialize): object {
                $rootResult = $model;

                foreach ($sorted as $collected) {
                    $this->propagateAggregateForeignKeys(
                        entity: $collected->entity,
                        metaData: $collected->metaData,
                    );

                    $saved = $this->save(
                        model: $collected->entity,
                        forceMaterialize: $forceMaterialize,
                        skipValidation: true,
                        skipCascade: true,
                    );

                    if ($saved !== $collected->entity) {
                        $this->copyBackAggregatePrimaryKey(
                            original: $collected->entity,
                            saved: $saved,
                            metaData: $collected->metaData,
                        );
                    }

                    $this->propagateAggregateChildForeignKeys(
                        entity: $collected->entity,
                        metaData: $collected->metaData,
                    );

                    $this->flushAggregatePivots(
                        entity: $collected->entity,
                        metaData: $collected->metaData,
                    );

                    if ($collected->entity === $model) {
                        $rootResult = $saved;
                    }
                }

                return $rootResult;
            },
        );

        return $result;
    }

    private function propagateAggregateChildForeignKeys(
        object $entity,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if (!RelationLoadState::isLoaded($entity, $relation)) {
                continue;
            }

            if ($attribute instanceof HasOne) {
                $child = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

                if (\is_object($child)) {
                    $this->writeHasFkOnChild(
                        parent: $entity,
                        parentMetaData: $metaData,
                        relation: $relation,
                        child: $child,
                    );
                }

                continue;
            }

            if ($attribute instanceof HasMany) {
                $value = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

                if ($value instanceof RelationInterface) {
                    foreach ($value as $child) {
                        $this->writeHasFkOnChild(
                            parent: $entity,
                            parentMetaData: $metaData,
                            relation: $relation,
                            child: $child,
                        );
                    }
                }

                continue;
            }

            if ($attribute instanceof MorphOne) {
                $child = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

                if (\is_object($child)) {
                    $this->writeMorphFkOnChild(
                        parent: $entity,
                        parentMetaData: $metaData,
                        relation: $relation,
                        attribute: $attribute,
                        child: $child,
                    );
                }

                continue;
            }

            if ($attribute instanceof MorphMany) {
                $value = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

                if ($value instanceof RelationInterface) {
                    foreach ($value as $child) {
                        $this->writeMorphFkOnChild(
                            parent: $entity,
                            parentMetaData: $metaData,
                            relation: $relation,
                            attribute: $attribute,
                            child: $child,
                        );
                    }
                }
            }
        }
    }

    private function writeHasFkOnChild(
        object $parent,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        object $child,
    ): void {
        $foreignKeyColumns = $relation->foreignKeyColumns;
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $childMetaData = $this->metaData->getModel($relation->relatedClass);

        foreach ($foreignKeyColumns as $index => $childColumn) {
            $parentColumn = $referencedKeyColumns[$index];
            $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
            $childProperty = $this->findPropertyForColumnOnMetadata($childMetaData, $childColumn);

            $value = PropertyReflector::createFromObject($parent, $parentProperty)->getValue($parent);

            if ($value === null) {
                return; // @codeCoverageIgnore
            }

            PropertyReflector::createFromObject($child, $childProperty)->setValue($child, $value);
        }
    }

    private function writeMorphFkOnChild(
        object $parent,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        MorphOne|MorphMany $attribute,
        object $child,
    ): void {
        $idColumns = $relation->foreignKeyColumns;
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($idColumns === null || $referencedKeyColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $childMetaData = $this->metaData->getModel($relation->relatedClass);
        $typeProperty = $this->findPropertyForColumnOnMetadata($childMetaData, $attribute->typeColumn);
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        PropertyReflector::createFromObject($child, $typeProperty)->setValue($child, $typeValue);

        foreach ($idColumns as $index => $childColumn) {
            $parentColumn = $referencedKeyColumns[$index];
            $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
            $childProperty = $this->findPropertyForColumnOnMetadata($childMetaData, $childColumn);

            $value = PropertyReflector::createFromObject($parent, $parentProperty)->getValue($parent);

            if ($value === null) {
                return; // @codeCoverageIgnore
            }

            PropertyReflector::createFromObject($child, $childProperty)->setValue($child, $value);
        }
    }

    private function flushAggregatePivots(
        object $entity,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if ($attribute instanceof BelongsToMany) {
                $this->flushBelongsToManyPivotChanges($entity, $relation);

                continue;
            }

            if ($attribute instanceof MorphToMany) {
                $this->flushMorphToManyPivotChanges($entity, $metaData, $relation);
            }
        }
    }

    private function copyBackAggregatePrimaryKey(
        object $original,
        object $saved,
        ModelMetaDataInterface $metaData,
    ): void {
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            return; // @codeCoverageIgnore
        }

        $property = $metaData->key->property;
        $savedValue = PropertyReflector::createFromObject($saved, $property)->getValue($saved);

        if ($savedValue === null) {
            return; // @codeCoverageIgnore
        }

        PropertyReflector::createFromObject($original, $property)->setValue($original, $savedValue);
    }

    /**
     * @param list<CollectedEntity> $entities
     *
     * @throws ModelException
     */
    private function guardAggregateConnections(
        array $entities,
    ): void {
        $connectionManager = null;

        foreach ($entities as $collected) {
            $declared = $collected->metaData->connection;

            if ($declared === null) {
                continue;
            }

            $connectionManager ??= $this->container->resolve(ConnectionManagerInterface::class);
            $declaredConnection = $connectionManager->getNamedConnection($declared);

            if ($declaredConnection === $this->connection) {
                continue;
            }

            throw ModelException::fromAggregateCrossConnection(
                modelClass: $collected->metaData->model,
                path: $collected->path,
                declaredConnection: $declared,
            );
        }
    }

    private function propagateAggregateForeignKeys(
        object $entity,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            if (!$relation->attribute instanceof BelongsTo) {
                continue;
            }

            if (!RelationLoadState::isLoaded($entity, $relation)) {
                continue;
            }

            $target = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

            if (!\is_object($target)) {
                continue; // @codeCoverageIgnore
            }

            $foreignKeyColumns = $relation->foreignKeyColumns;
            $referencedKeyColumns = $relation->referencedKeyColumns;

            if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
                continue; // @codeCoverageIgnore
            }

            $targetMetaData = $this->metaData->getModel($relation->relatedClass);

            foreach ($foreignKeyColumns as $index => $sourceColumn) {
                $targetColumn = $referencedKeyColumns[$index];
                $ownerProperty = $this->findPropertyForColumnOnMetadata($targetMetaData, $targetColumn);
                $ownerValue = PropertyReflector::createFromObject($target, $ownerProperty)->getValue($target);

                if ($ownerValue === null) {
                    continue 2; // @codeCoverageIgnore
                }

                $foreignKeyProperty = $this->findPropertyForColumnOnMetadata($metaData, $sourceColumn);

                PropertyReflector::createFromObject($entity, $foreignKeyProperty)->setValue($entity, $ownerValue);
            }
        }

        foreach ($metaData->morphToRelations as $morphTo) {
            if (!RelationLoadState::isLoaded($entity, $morphTo)) {
                continue;
            }

            $target = PropertyReflector::createFromObject($entity, $morphTo->property)->getValue($entity);

            if (!\is_object($target)) {
                continue; // @codeCoverageIgnore
            }

            $targetMetaData = $this->metaData->getModel($target::class);
            $targetPkValues = \array_values($this->resolveOwnKeyValues($target, $targetMetaData));

            $typeValue = MorphTypeResolver::encode(
                class: $target::class,
                typeMap: $morphTo->typeMap,
            );

            $typeProperty = $this->findPropertyForColumnOnMetadata($metaData, $morphTo->typeColumn);
            PropertyReflector::createFromObject($entity, $typeProperty)->setValue($entity, $typeValue);

            foreach ($morphTo->idColumns as $index => $idColumn) {
                $idProperty = $this->findPropertyForColumnOnMetadata($metaData, $idColumn);
                PropertyReflector::createFromObject($entity, $idProperty)->setValue($entity, $targetPkValues[$index]);
            }
        }
    }

    #[\NoDiscard]
    public function createTable(
        string $modelClass,
    ): CreateTableStatementInterface {
        $metaData = $this->metaData->getModel($modelClass);
        $statement = $this->connection->createTable(
            table: $metaData->table,
        );

        foreach ($metaData->columns as $column) {
            $column->attribute->toColumnType(
                statement: $statement,
                propertyName: $column->property,
            );
        }

        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            $statement->primaryKey(...$metaData->key->columns);
        }

        foreach ($metaData->uniques as $uniqueColumns) {
            $statement->unique(...$uniqueColumns);
        }

        foreach ($metaData->indexes as $indexColumns) {
            $statement->index(...$indexColumns);
        }

        foreach ($metaData->identifiers as $identifier) {
            $alreadyUnique = false;

            foreach ($metaData->columns as $column) {
                if ($column->column === $identifier->column && $column->unique) {
                    $alreadyUnique = true;

                    break;
                }
            }

            if (!$alreadyUnique) {
                $statement->unique($identifier->column);
            }
        }

        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if (!$attribute instanceof BelongsTo) {
                continue;
            }

            $targetMetaData = $this->metaData->getModel($relation->relatedClass);
            $foreignKeyColumns = $relation->foreignKeyColumns;
            $referencedKeyColumns = $relation->referencedKeyColumns;

            if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
                continue; // @codeCoverageIgnore
            }

            $statement->foreignKey(
                columns: $foreignKeyColumns,
                referencedTable: $targetMetaData->table,
                referencedColumns: $referencedKeyColumns,
            );
        }

        foreach ($metaData->morphToRelations as $morphTo) {
            $statement->index($morphTo->typeColumn, ...$morphTo->idColumns);
        }

        return $statement;
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
        bool $skipCascade = false,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if (!$skipCascade) {
            $this->cascadeSaveMorphToRelations($model, $metaData, $forceMaterialize);
        }

        if ($this->isNewModel($model, $metaData)) {
            $this->dispatchBeforeInsert($model, $metaData);

            $target = $metaData->readonly
                ? $model
                : clone $model;

            $result = $this->insert($target, $metaData);
        } else {
            $dirty = $this->getDirtyColumnsExcludingKeys($model, $metaData);

            if ($dirty === []) {
                $result = $model;
            } else {
                $this->dispatchBeforeUpdate($model, $metaData);

                $result = $this->update($model, $metaData);
            }
        }

        if (!$skipCascade) {
            $this->cascadeSaveRelations($result, $metaData, $forceMaterialize);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDirtyColumnsExcludingKeys(
        object $model,
        ModelMetaDataInterface $metaData,
    ): array {
        $dirty = $this->dirtyTracker->getDirtyColumns($model, $metaData);

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            unset($dirty[$metaData->key->column]);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach ($metaData->key->columns as $column) {
                unset($dirty[$column]);
            }
        }

        return $dirty;
    }

    private function dispatchBeforeInsert(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->behaviorsOf(BeforeInsertBehaviorInterface::class) as $property => $behaviorClass) {
            $column = $metaData->columnFor($property);

            if ($column === null) {
                continue; // @codeCoverageIgnore
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
                continue; // @codeCoverageIgnore
            }

            $this->getBehaviorFor($behaviorClass)->beforeUpdate($model, $column);
        }
    }

    private function isNewModel(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        if ($metaData->key === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        if (
            $metaData->key instanceof ModelPrimaryKeyInterface &&
            $metaData->key->autoIncrement
        ) {
            return PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model) === null;
        }

        return !$this->dirtyTracker->hasSnapshot($model);
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
                // @codeCoverageIgnoreStart
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $column->property,
                );
                // @codeCoverageIgnoreEnd
            }

            if ($value !== null) {
                $coercer = $this->getCoercerFor($column->attribute);

                if ($coercer !== null) {
                    $value = $coercer->dehydrate($value);
                } elseif (!\is_scalar($value)) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $column->property,
                        actualType: \get_debug_type($value),
                    );
                    // @codeCoverageIgnoreEnd
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
        $dirty = $this->getDirtyColumnsExcludingKeys($model, $metaData);

        if ($dirty === []) {
            return $model; // @codeCoverageIgnore
        }

        $query = $this->connection->update($metaData->table);

        foreach ($metaData->columns as $modelColumn) {
            if (!\array_key_exists($modelColumn->column, $dirty)) {
                continue;
            }

            $value = $dirty[$modelColumn->column];

            if ($value === null && !$modelColumn->nullable) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $modelColumn->property,
                );
                // @codeCoverageIgnoreEnd
            }

            $value = $this->dehydrateColumnValue($metaData, $modelColumn->property, $value);

            $query->set($modelColumn->column, $value);
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);
            $value = $this->dehydrateColumnValue($metaData, $metaData->key->property, $value);

            if ($value === null) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: 'null',
                );
                // @codeCoverageIgnoreEnd
            }

            $query->where($metaData->key->column, $value);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($model, $property)->getValue($model);
                $value = $this->dehydrateColumnValue($metaData, $property, $value);

                if ($value === null) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: 'null',
                    );
                    // @codeCoverageIgnoreEnd
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

            if ($attribute instanceof BelongsToMany) {
                $this->flushBelongsToManyPivotChanges($model, $relation);
            }

            if ($attribute instanceof MorphToMany) {
                $this->flushMorphToManyPivotChanges($model, $metaData, $relation);
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

                continue;
            }

            if ($attribute instanceof MorphOne) {
                $this->cascadeSaveMorphSingleObjectRelation($model, $metaData, $relation, $forceMaterialize);

                continue;
            }

            if ($attribute instanceof MorphMany) {
                $this->cascadeSaveMorphCollectionRelation($model, $metaData, $relation, $forceMaterialize);

                continue;
            }

            if ($attribute instanceof MorphToMany) {
                $this->cascadeSaveMorphToManyRelation($model, $relation, $forceMaterialize);
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

        $attribute = $relation->attribute;

        if ($attribute instanceof HasOne) {
            $parentMetaData = $this->metaData->getModel($model::class);

            $this->writeHasFkOnChild(
                parent: $model,
                parentMetaData: $parentMetaData,
                relation: $relation,
                child: $value,
            );
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
            return; // @codeCoverageIgnore
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

        foreach ($value as $item) {
            $this->writeHasFkOnChild(
                parent: $model,
                parentMetaData: $parentMetaData,
                relation: $relation,
                child: $item,
            );

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
            return; // @codeCoverageIgnore
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
    }

    private function flushBelongsToManyPivotChanges(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof BelongsToMany) {
            return; // @codeCoverageIgnore
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        if ($value->pendingAdds === [] && $value->pendingRemoves === []) {
            return;
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyValues = $this->resolveOwnKeyValues($model, $parentMetaData);
        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);

        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $localValueList = \array_values($localKeyValues);

        foreach ($value->pendingRemoves as $item) {
            $foreignKeyValues = \array_values($this->resolveOwnKeyValues($item, $relatedMetaData));

            $statement = $this->connection->delete($attribute->table);

            foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                $statement->where($pivotSourceColumn, $localValueList[$index]);
            }

            foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                $statement->where($pivotTargetColumn, $foreignKeyValues[$index]);
            }

            $statement->execute();
        }

        foreach ($value->pendingAdds as $item) {
            $foreignKeyValues = \array_values($this->resolveOwnKeyValues($item, $relatedMetaData));

            $statement = $this->connection->insert($attribute->table);

            foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                $statement->set($pivotSourceColumn, $localValueList[$index]);
            }

            foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                $statement->set($pivotTargetColumn, $foreignKeyValues[$index]);
            }

            $statement->execute();
        }

        $value->clearPending();
    }

    private function cascadeSaveMorphToRelations(
        object $model,
        ModelMetaDataInterface $metaData,
        bool $forceMaterialize,
    ): void {
        foreach ($metaData->morphToRelations as $morphTo) {
            if ($morphTo->attribute->onSave !== CascadeAction::CASCADE) {
                continue;
            }

            $value = PropertyReflector::createFromObject($model, $morphTo->property)->getValue($model);

            if (!\is_object($value)) {
                continue;
            }

            $reflection = new \ReflectionClass($value);

            if ($reflection->isUninitializedLazyObject($value)) {
                if (!$forceMaterialize) {
                    continue;
                }

                $reflection->initializeLazyObject($value);
            }

            $saved = $this->save(
                model: $value,
                forceMaterialize: $forceMaterialize,
            );

            $targetMetaData = $this->metaData->getModel($saved::class);
            $targetPkValues = $this->resolveOwnKeyValues($saved, $targetMetaData);
            $typeValue = MorphTypeResolver::encode(
                class: $saved::class,
                typeMap: $morphTo->typeMap,
            );

            $typeProperty = $this->findPropertyForColumnOnMetadata($metaData, $morphTo->typeColumn);
            PropertyReflector::createFromObject($model, $typeProperty)->setValue($model, $typeValue);

            $targetPkValueList = \array_values($targetPkValues);

            foreach ($morphTo->idColumns as $index => $idColumn) {
                $idProperty = $this->findPropertyForColumnOnMetadata($metaData, $idColumn);
                PropertyReflector::createFromObject($model, $idProperty)->setValue($model, $targetPkValueList[$index]);
            }
        }
    }

    private function cascadeSaveMorphSingleObjectRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        /** @var MorphOne $attribute */
        $attribute = $relation->attribute;
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

        $this->writeMorphFkOnChild(
            parent: $model,
            parentMetaData: $parentMetaData,
            relation: $relation,
            attribute: $attribute,
            child: $value,
        );

        (void) $this->save(
            model: $value,
            forceMaterialize: $forceMaterialize,
        );
    }

    private function cascadeSaveMorphCollectionRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        /** @var MorphMany $attribute */
        $attribute = $relation->attribute;
        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        $hasPending = $value->pendingAdds !== [] || $value->pendingRemoves !== [];

        if (!$value->isMaterialized() && !$hasPending && !$forceMaterialize) {
            return;
        }

        foreach ($value as $item) {
            $this->writeMorphFkOnChild(
                parent: $model,
                parentMetaData: $parentMetaData,
                relation: $relation,
                attribute: $attribute,
                child: $item,
            );

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

    private function cascadeSaveMorphToManyRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $forceMaterialize,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof MorphToMany) {
            return; // @codeCoverageIgnore
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
    }

    private function flushMorphToManyPivotChanges(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof MorphToMany) {
            return; // @codeCoverageIgnore
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return;
        }

        if ($value->pendingAdds === [] && $value->pendingRemoves === []) {
            return;
        }

        $localKeyValues = $this->resolveOwnKeyValues($model, $parentMetaData);
        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $localValueList = \array_values($localKeyValues);

        foreach ($value->pendingRemoves as $item) {
            $foreignKeyValues = \array_values($this->resolveOwnKeyValues($item, $relatedMetaData));

            $statement = $this->connection
                ->delete($attribute->table)
                ->where($attribute->typeColumn, $typeValue);

            foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                $statement->where($pivotSourceColumn, $localValueList[$index]);
            }

            foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                $statement->where($pivotTargetColumn, $foreignKeyValues[$index]);
            }

            $statement->execute();
        }

        foreach ($value->pendingAdds as $item) {
            $foreignKeyValues = \array_values($this->resolveOwnKeyValues($item, $relatedMetaData));

            $statement = $this->connection
                ->insert($attribute->table)
                ->set($attribute->typeColumn, $typeValue);

            foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                $statement->set($pivotSourceColumn, $localValueList[$index]);
            }

            foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                $statement->set($pivotTargetColumn, $foreignKeyValues[$index]);
            }

            $statement->execute();
        }

        $value->clearPending();
    }

    private function findPropertyForColumnOnMetadata(
        ModelMetaDataInterface $metaData,
        string $columnName,
    ): string {
        foreach ($metaData->columns as $column) {
            if ($column->column === $columnName) {
                return $column->property;
            }
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromPropertyIsNotAColumn(
            modelClass: $metaData->model,
            property: $columnName,
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return non-empty-array<string, string|int|float|bool>
     *
     * @throws ModelException
     */
    private function resolveOwnKeyValues(
        object $model,
        ModelMetaDataInterface $metaData,
    ): array {
        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            $values = [];
            $columns = \array_values($metaData->key->columns);
            $properties = \array_values($metaData->key->properties);

            foreach ($columns as $index => $column) {
                $property = $properties[$index];
                $raw = PropertyReflector::createFromObject($model, $property)->getValue($model);
                $value = $this->dehydrateColumnValue($metaData, $property, $raw);

                if ($value === null) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: 'null',
                    );
                    // @codeCoverageIgnoreEnd
                }

                $values[$column] = $value;
            }

            return $values;
        }

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        $raw = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);
        $value = $this->dehydrateColumnValue($metaData, $metaData->key->property, $raw);

        if ($value === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $metaData->key->property,
                actualType: 'null',
            );
            // @codeCoverageIgnoreEnd
        }

        return [
            $metaData->key->column => $value,
        ];
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

            $attribute = $relation->attribute;

            if ($action === CascadeAction::RESTRICT) {
                if ($attribute instanceof MorphOne || $attribute instanceof MorphMany) {
                    $this->cascadeDeleteMorphRestrictRelation($model, $metaData, $relation);
                } else {
                    $this->cascadeDeleteRestrictRelation($model, $relation);
                }

                continue;
            }

            if ($action === CascadeAction::SET_NULL) {
                if ($attribute instanceof MorphOne || $attribute instanceof MorphMany) {
                    $this->cascadeDeleteMorphSetNullRelation($model, $metaData, $relation);
                } else {
                    $this->cascadeDeleteSetNullRelation($model, $relation);
                }

                continue;
            }

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

            if ($attribute instanceof MorphOne) {
                $this->cascadeDeleteMorphSingleObjectRelation($model, $metaData, $relation, $force);

                continue;
            }

            if ($attribute instanceof MorphMany) {
                $this->cascadeDeleteMorphCollectionRelation($model, $metaData, $relation, $force);

                continue;
            }

            if ($attribute instanceof MorphToMany) {
                $this->cascadeDeleteMorphToManyPivot($model, $metaData, $relation);

                continue;
            }
        }
    }

    private function cascadeDeleteSingleObjectRelation(
        object $model,
        ModelRelationInterface $relation,
        bool $force,
    ): void {
        $attribute = $relation->attribute;

        if ($attribute instanceof HasOne) {
            $parentMetaData = $this->metaData->getModel($model::class);
            $foreignKeyColumns = $relation->foreignKeyColumns;
            $referencedKeyColumns = $relation->referencedKeyColumns;

            if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromRelationNotFoundOnModel(
                    modelClass: $parentMetaData->model,
                    property: $relation->property,
                );
                // @codeCoverageIgnoreEnd
            }

            $parentValues = [];

            foreach ($referencedKeyColumns as $parentColumn) {
                $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
                $raw = PropertyReflector::createFromObject($model, $parentProperty)->getValue($model);
                $value = $this->dehydrateColumnValue($parentMetaData, $parentProperty, $raw);

                if (!\is_scalar($value)) {
                    return; // @codeCoverageIgnore
                }

                $parentValues[] = $value;
            }

            $child = $this->findFirst(
                $relation->relatedClass,
                static function (SelectStatementInterface $statement) use ($foreignKeyColumns, $parentValues): void {
                    foreach ($foreignKeyColumns as $index => $childColumn) {
                        $statement->where($childColumn, $parentValues[$index]);
                    }
                },
            );

            if ($child === null) {
                return;
            }

            if ($force) {
                (void) $this->forceDelete($child);

                return;
            }

            (void) $this->delete($child);

            return;
        }

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
            return; // @codeCoverageIgnore
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
        $foreignKeyColumns = $relation->foreignKeyColumns;
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
            return; // @codeCoverageIgnore
        }

        $childMetaData = $this->metaData->getModel($relation->relatedClass);
        $statement = $this->connection->delete($childMetaData->table);

        foreach ($foreignKeyColumns as $index => $childColumn) {
            $parentColumn = $referencedKeyColumns[$index];
            $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
            $parentValue = PropertyReflector::createFromObject($model, $parentProperty)->getValue($model);
            $parentValue = $this->dehydrateColumnValue($parentMetaData, $parentProperty, $parentValue);

            if ($parentValue === null) {
                return; // @codeCoverageIgnore
            }

            $statement->where($childColumn, $parentValue);
        }

        $statement->execute();
    }

    private function cascadeDeleteBelongsToManyPivot(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof BelongsToMany) {
            return; // @codeCoverageIgnore
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $localKeyValues = $this->resolveOwnKeyValues($model, $parentMetaData);
        $pivotSourceColumns = $relation->pivotSourceColumns;

        if ($pivotSourceColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $localValueList = \array_values($localKeyValues);
        $statement = $this->connection->delete($attribute->table);

        foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
            $statement->where($pivotSourceColumn, $localValueList[$index]);
        }

        $statement->execute();
    }

    private function cascadeDeleteRestrictRelation(
        object $model,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if ($attribute instanceof HasOne) {
            $parentMetaData = $this->metaData->getModel($model::class);
            $foreignKeyColumns = $relation->foreignKeyColumns;
            $referencedKeyColumns = $relation->referencedKeyColumns;

            if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
                return; // @codeCoverageIgnore
            }

            $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
            $statement = $this->connection->count($relatedMetaData->table);

            foreach ($foreignKeyColumns as $index => $childColumn) {
                $parentColumn = $referencedKeyColumns[$index];
                $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
                $parentValue = PropertyReflector::createFromObject($model, $parentProperty)->getValue($model);
                $parentValue = $this->dehydrateColumnValue($parentMetaData, $parentProperty, $parentValue);

                if (!\is_scalar($parentValue)) {
                    return; // @codeCoverageIgnore
                }

                $statement->where($childColumn, $parentValue);
            }

            $count = $statement->count();

            if ($count === 0) {
                return;
            }

            throw ModelException::fromRestrictedRelation(
                modelClass: $model::class,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        if ($attribute instanceof HasMany) {
            $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

            if (!$value instanceof RelationInterface) {
                return; // @codeCoverageIgnore
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
            return; // @codeCoverageIgnore
        }

        $parentMetaData = $this->metaData->getModel($model::class);
        $foreignKeyColumns = $relation->foreignKeyColumns;
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($foreignKeyColumns === null || $referencedKeyColumns === null) {
            return; // @codeCoverageIgnore
        }

        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
        $statement = $this->connection->update($relatedMetaData->table);

        foreach ($foreignKeyColumns as $childColumn) {
            $statement->set($childColumn, null);
        }

        foreach ($foreignKeyColumns as $index => $childColumn) {
            $parentColumn = $referencedKeyColumns[$index];
            $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
            $parentValue = PropertyReflector::createFromObject($model, $parentProperty)->getValue($model);
            $parentValue = $this->dehydrateColumnValue($parentMetaData, $parentProperty, $parentValue);

            if ($parentValue === null) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $parentMetaData->model,
                    property: $parentProperty,
                    actualType: 'null',
                );
                // @codeCoverageIgnoreEnd
            }

            $statement->where($childColumn, $parentValue);
        }

        $statement->execute();
    }

    private function cascadeDeleteMorphSingleObjectRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        bool $force,
    ): void {
        /** @var MorphOne $attribute */
        $attribute = $relation->attribute;
        $parentTuple = $this->readMorphParentTuple($model, $parentMetaData, $relation);

        if ($parentTuple === null) {
            return; // @codeCoverageIgnore
        }

        $typeColumn = $attribute->typeColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        /** @var non-empty-list<string> $idColumns */
        $idColumns = $relation->foreignKeyColumns;

        $child = $this->findFirst(
            $relation->relatedClass,
            static function (SelectStatementInterface $statement) use ($typeColumn, $idColumns, $typeValue, $parentTuple): void {
                $statement->where($typeColumn, $typeValue);

                foreach ($idColumns as $index => $idColumn) {
                    $statement->where($idColumn, $parentTuple[$index]);
                }
            },
        );

        if ($child === null) {
            return;
        }

        if ($force) {
            (void) $this->forceDelete($child);

            return;
        }

        (void) $this->delete($child);
    }

    private function cascadeDeleteMorphCollectionRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        bool $force,
    ): void {
        /** @var MorphMany $attribute */
        $attribute = $relation->attribute;

        if ($attribute->bulkDelete) {
            $this->cascadeMorphBulkDeleteRelation($model, $parentMetaData, $relation, $attribute);

            return;
        }

        $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

        if (!$value instanceof RelationInterface) {
            return; // @codeCoverageIgnore
        }

        foreach ($value as $item) {
            if ($force) {
                (void) $this->forceDelete($item);

                continue;
            }

            (void) $this->delete($item);
        }
    }

    private function cascadeMorphBulkDeleteRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
        MorphMany $attribute,
    ): void {
        $parentTuple = $this->readMorphParentTuple($model, $parentMetaData, $relation);

        if ($parentTuple === null) {
            return; // @codeCoverageIgnore
        }

        /** @var non-empty-list<string> $idColumns */
        $idColumns = $relation->foreignKeyColumns;

        $childMetaData = $this->metaData->getModel($relation->relatedClass);
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        $statement = $this->connection
            ->delete($childMetaData->table)
            ->where($attribute->typeColumn, $typeValue);

        foreach ($idColumns as $index => $idColumn) {
            $statement->where($idColumn, $parentTuple[$index]);
        }

        $statement->execute();
    }

    private function cascadeDeleteMorphToManyPivot(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (!$attribute instanceof MorphToMany) {
            return; // @codeCoverageIgnore
        }

        $localKeyValues = $this->resolveOwnKeyValues($model, $parentMetaData);
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        $pivotSourceColumns = $relation->pivotSourceColumns;

        if ($pivotSourceColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $localValueList = \array_values($localKeyValues);
        $statement = $this->connection
            ->delete($attribute->table)
            ->where($attribute->typeColumn, $typeValue);

        foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
            $statement->where($pivotSourceColumn, $localValueList[$index]);
        }

        $statement->execute();
    }

    private function cascadeDeleteMorphRestrictRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if ($attribute instanceof MorphOne) {
            $parentTuple = $this->readMorphParentTuple($model, $parentMetaData, $relation);

            if ($parentTuple === null) {
                return; // @codeCoverageIgnore
            }

            /** @var non-empty-list<string> $idColumns */
            $idColumns = $relation->foreignKeyColumns;

            $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
            $typeValue = MorphTypeResolver::encode(
                class: $parentMetaData->model,
                typeMap: $attribute->typeMap,
            );

            $statement = $this->connection->count($relatedMetaData->table)
                ->where($attribute->typeColumn, $typeValue);

            foreach ($idColumns as $index => $idColumn) {
                $statement->where($idColumn, $parentTuple[$index]);
            }

            $count = $statement->count();

            if ($count === 0) {
                return;
            }

            throw ModelException::fromRestrictedRelation(
                modelClass: $model::class,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        if ($attribute instanceof MorphMany) {
            $value = PropertyReflector::createFromObject($model, $relation->property)->getValue($model);

            if (!$value instanceof RelationInterface) {
                return; // @codeCoverageIgnore
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

    private function cascadeDeleteMorphSetNullRelation(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): void {
        $attribute = $relation->attribute;

        if (
            !$attribute instanceof MorphOne &&
            !$attribute instanceof MorphMany
        ) {
            return; // @codeCoverageIgnore
        }

        $parentTuple = $this->readMorphParentTuple($model, $parentMetaData, $relation);

        if ($parentTuple === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $parentMetaData->model,
                property: $relation->property,
                actualType: 'null',
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var non-empty-list<string> $idColumns */
        $idColumns = $relation->foreignKeyColumns;

        $relatedMetaData = $this->metaData->getModel($relation->relatedClass);
        $typeValue = MorphTypeResolver::encode(
            class: $parentMetaData->model,
            typeMap: $attribute->typeMap,
        );

        $statement = $this->connection
            ->update($relatedMetaData->table)
            ->set($attribute->typeColumn, null);

        foreach ($idColumns as $idColumn) {
            $statement->set($idColumn, null);
        }

        $statement->where($attribute->typeColumn, $typeValue);

        foreach ($idColumns as $index => $idColumn) {
            $statement->where($idColumn, $parentTuple[$index]);
        }

        $statement->execute();
    }

    /**
     * @return non-empty-list<int|string>|null
     *
     * @throws ModelException
     */
    private function readMorphParentTuple(
        object $model,
        ModelMetaDataInterface $parentMetaData,
        ModelRelationInterface $relation,
    ): ?array {
        $referencedKeyColumns = $relation->referencedKeyColumns;

        if ($referencedKeyColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $parentMetaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $tuple = [];

        foreach ($referencedKeyColumns as $parentColumn) {
            $parentProperty = $this->findPropertyForColumnOnMetadata($parentMetaData, $parentColumn);
            $raw = PropertyReflector::createFromObject($model, $parentProperty)->getValue($model);
            $value = $this->dehydrateColumnValue($parentMetaData, $parentProperty, $raw);

            if (!\is_int($value) && !\is_string($value)) {
                return null; // @codeCoverageIgnore
            }

            $tuple[] = $value;
        }

        return $tuple;
    }
    /**
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $explicitWith
     * @return array<string, (\Closure(Relation<object>): Relation<object>)|null>
     */
    public function mergeAutoEagerWith(
        ModelMetaDataInterface $metaData,
        ?array $explicitWith,
    ): array {
        $merged = $explicitWith ?? [];

        foreach ($metaData->relations as $relation) {
            if (!$relation->attribute instanceof HasOne) {
                continue;
            }

            if (!$relation->nullable) {
                continue;
            }

            if (\array_key_exists($relation->property, $merged)) {
                continue;
            }

            $merged[$relation->property] = static fn (Relation $r): Relation => $r;
        }

        return $merged;
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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

        $effectiveWith = $this->mergeAutoEagerWith($metaData, $with);

        if (\sizeof($effectiveWith) > 0) {
            $this->hydrator->eagerLoad(
                parents: [
                    $result,
                ],
                with: $effectiveWith,
            );
        }

        return $result;
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findById(
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
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        return $this->findById($class, $id, $criteria, $includeDeleted, $with) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
        int $chunkSize = self::DEFAULT_EAGER_CHUNK_SIZE,
    ): \Generator {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        if (!$includeDeleted) {
            $this->applySoftDeleteFilter($query, $metaData);
        }

        $effectiveWith = $this->mergeAutoEagerWith($metaData, $with);

        if (\sizeof($effectiveWith) === 0) {
            yield from $query->fetchAll($class, $this->hydrator);

            return;
        }

        $buffer = [];

        foreach ($query->fetchAll($class, $this->hydrator) as $model) {
            $buffer[] = $model;

            if (\sizeof($buffer) < $chunkSize) {
                continue;
            }

            $this->hydrator->eagerLoad(
                parents: $buffer,
                with: $effectiveWith,
            );

            foreach ($buffer as $item) {
                yield $item;
            }

            $buffer = [];
        }

        if (\sizeof($buffer) > 0) {
            $this->hydrator->eagerLoad(
                parents: $buffer,
                with: $effectiveWith,
            );

            foreach ($buffer as $item) {
                yield $item;
            }
        }
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

        $fresh = $this->findById($model::class, $value, includeDeleted: true);

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
    public function existsById(
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

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @return Query<TModel>
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function query(
        string $class,
        bool $includeDeleted = false,
    ): Query {
        $metaData = $this->metaData->getModel($class);
        $manager = $this;

        return Query::createFromBuilder(
            loaderBuilder: static function (array $criteria, array $orderBy, ?int $limit, ?int $offset) use ($manager, $class, $metaData, $includeDeleted): iterable {
                $statement = $manager->connection->select($metaData->table);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                if (!$includeDeleted) {
                    $manager->applySoftDeleteFilter($statement, $metaData);
                }

                foreach ($orderBy as $spec) {
                    $statement->orderBy($spec['column'], $spec['direction']);
                }

                if ($limit !== null) {
                    $statement->limit($limit, $offset);
                }

                return $statement->fetchAll($class, $manager->hydrator);
            },
            countBuilder: static function (array $criteria) use ($manager, $metaData, $includeDeleted): int {
                $statement = $manager->connection->count($metaData->table);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                if (!$includeDeleted) {
                    $manager->applySoftDeleteFilter($statement, $metaData);
                }

                return $statement->count();
            },
            eagerLoader: static function (array $parents, array $with) use ($manager): void {
                $manager->hydrator->eagerLoad(
                    parents: $parents,
                    with: $with,
                );
            },
            manager: $this,
            modelClass: $class,
            with: $this->mergeAutoEagerWith($metaData, null),
        );
    }

    #[\NoDiscard]
    public function delete(
        object $model,
    ): bool {
        if (isset($this->deleteInProgress[$model])) {
            return true; // @codeCoverageIgnore
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
            return true; // @codeCoverageIgnore
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
            // @codeCoverageIgnoreStart
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
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
                continue; // @codeCoverageIgnore
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
                continue; // @codeCoverageIgnore
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
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: 'null',
                );
                // @codeCoverageIgnoreEnd
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
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: 'null',
                    );
                    // @codeCoverageIgnoreEnd
                }

                $query->where($column, $value);
            }
        }
    }

    public function applySoftDeleteFilter(
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
            return; // @codeCoverageIgnore
        }

        $query->whereNull($metaData->table . '.' . $column->column);
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
