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

namespace Tuxxedo\Model\Hydrator;

use Tuxxedo\Database\Hydrator\HydratorInterface as DatabaseHydratorInterface;
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Relation\RelationKeyTupleHasher;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\MetaData\MorphToRelationMetaDataInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Model\MorphTypeResolver;
use Tuxxedo\Model\Relation;
use Tuxxedo\Reflection\PropertyReflector;

class Hydrator implements HydratorInterface
{
    public function __construct(
        private readonly ModelsManagerInterface $modelsManager,
        private readonly MetaDataInterface $metaData,
        private readonly DatabaseHydratorInterface $hydrator,
    ) {
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     */
    public function hydrate(
        string $className,
        array $values,
    ): object {
        $propertyValues = [];
        $metaData = $this->metaData->getModel($className);

        foreach ($metaData->columns as $column) {
            if (!\array_key_exists($column->column, $values)) {
                continue;
            }

            $value = $values[$column->column];

            if ($value === null) {
                $propertyValues[$column->property] = null;

                continue;
            }

            $coercer = $this->modelsManager->getCoercerFor($column->attribute);

            if ($coercer === null) {
                $propertyValues[$column->property] = $value;

                continue;
            }

            if (!\is_scalar($value)) {
                throw ModelException::fromCoercionFailure(
                    coercerClass: $coercer::class,
                    expectedType: 'int|string|float|bool',
                    actualType: \get_debug_type($value),
                );
            }

            $propertyValues[$column->property] = $coercer->hydrate($value);
        }

        $model = $this->hydrator->hydrate($className, $propertyValues);

        $this->applyAggregates($model, $metaData, $values);
        $this->attachRelations($model, $metaData);
        $this->modelsManager->dirtyTracker->recordSnapshot($model, $metaData);

        return $model;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function applyAggregates(
        object $model,
        ModelMetaDataInterface $metaData,
        array $values,
    ): void {
        foreach ($metaData->aggregates as $aggregate) {
            if (!\array_key_exists($aggregate->alias, $values)) {
                continue;
            }

            $rawValue = $values[$aggregate->alias];

            if ($rawValue === null) {
                PropertyReflector::createFromObject($model, $aggregate->property)->setValue($model, null);

                continue;
            }

            if (!\is_scalar($rawValue)) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $casted = $aggregate->slotType === 'int'
                ? (int) $rawValue
                : (float) $rawValue;

            PropertyReflector::createFromObject($model, $aggregate->property)->setValue($model, $casted);
        }
    }

    private function attachRelations(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        foreach ($metaData->relations as $relation) {
            $attribute = $relation->attribute;

            if ($attribute instanceof MorphOne) {
                $this->setupMorphOneRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof MorphMany) {
                $this->setupMorphManyRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof MorphToMany) {
                $this->setupMorphToManyRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof HasOne || $attribute instanceof BelongsTo) {
                $this->setupSingleObjectRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof HasMany) {
                $this->setupHasManyRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->setupBelongsToManyRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof HasOneThrough) {
                $this->setupHasOneThroughRelation($model, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof HasManyThrough) {
                $this->setupHasManyThroughRelation($model, $metaData, $relation);

                continue;
            }
        }

        foreach ($metaData->morphToRelations as $morphTo) {
            $this->setupMorphToRelation($model, $metaData, $morphTo);
        }
    }

    private function setupSingleObjectRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $sourceColumns = $this->resolveSourceColumns($metaData, $relation);
        $sourceValues = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyName = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );

            $sourceValue = PropertyReflector::createFromObject($model, $sourcePropertyName)->getValue($model);

            if ($sourceValue === null) {
                if (!$relation->nullable) {
                    throw ModelException::fromMissingForeignKeyValue(
                        modelClass: $metaData->model,
                        property: $relation->property,
                    );
                }

                PropertyReflector::createFromObject($model, $relation->property)->setValue($model, null);

                return;
            }

            if (!\is_scalar($sourceValue)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($sourceValue),
                );
                // @codeCoverageIgnoreEnd
            }

            $sourceValues[$sourceColumn] = $sourceValue;
        }

        $relatedClass = new \ReflectionClass($relation->relatedClass);
        $proxy = $relatedClass->newLazyProxy(
            fn (): object => $this->loadSingleRelation($metaData, $relation, $sourceValues),
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $proxy);
    }

    private function setupHasManyRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $sourceColumns = $this->resolveSourceColumns($metaData, $relation);
        $targetColumns = $this->resolveTargetColumns($relation);
        $relatedClass = $relation->relatedClass;
        $sourceValues = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyName = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );

            $value = PropertyReflector::createFromObject($model, $sourcePropertyName)->getValue($model);

            if ($value === null) {
                PropertyReflector::createFromObject($model, $relation->property)->setValue(
                    $model,
                    Relation::createFromPrefetched(
                        values: [],
                        manager: $this->modelsManager,
                        modelClass: $relatedClass,
                    ),
                );

                return;
            }

            if (!\is_scalar($value)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
                // @codeCoverageIgnoreEnd
            }

            $sourceValues[$sourceColumn] = $value;
        }

        $manager = $this->modelsManager;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($sourceColumns, $targetColumns, $sourceValues, $criteria, $orderBy, $limit, $offset): void {
                    foreach ($sourceColumns as $index => $sourceColumn) {
                        $targetColumn = $targetColumns[$index];
                        $statement->where($targetColumn, $sourceValues[$sourceColumn]);
                    }

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $sourceColumns, $targetColumns, $sourceValues): int {
                $statement = $manager->connection->count($targetTable);

                foreach ($sourceColumns as $index => $sourceColumn) {
                    $targetColumn = $targetColumns[$index];
                    $statement->where($targetColumn, $sourceValues[$sourceColumn]);
                }

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            manager: $manager,
            modelClass: $relatedClass,
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function setupBelongsToManyRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        $parentPkColumns = $this->resolvePkColumns($metaData);
        $targetPkColumns = $this->resolvePkColumns($targetMetaData);
        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyNames = [];

        foreach ($parentPkColumns as $parentColumn) {
            $sourcePropertyNames[$parentColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $parentColumn,
                relationProperty: $relation->property,
            );
        }

        $parentValues = $this->readTupleFromModel($model, $metaData, $parentPkColumns, $sourcePropertyNames);

        if ($parentValues === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched(
                    values: [],
                    manager: $this->modelsManager,
                    modelClass: $relatedClass,
                ),
            );

            return;
        }

        /** @var BelongsToMany $attribute */
        $attribute = $relation->attribute;
        $targetTable = $targetMetaData->table;
        $pivotTable = $attribute->table;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                function (SelectStatementInterface $statement) use ($pivotTable, $pivotSourceColumns, $pivotTargetColumns, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues, $criteria, $orderBy, $limit, $offset): void {
                    $this->applyBelongsToManyPivotJoin(
                        statement: $statement,
                        pivotTable: $pivotTable,
                        pivotTargetColumns: $pivotTargetColumns,
                        targetTable: $targetTable,
                        targetPkColumns: $targetPkColumns,
                        pivotSourceColumns: $pivotSourceColumns,
                        parentPkColumns: $parentPkColumns,
                        parentValues: $parentValues,
                    );

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: function (array $criteria) use ($manager, $pivotTable, $pivotSourceColumns, $pivotTargetColumns, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues): int {
                $statement = $manager->connection->count($targetTable);

                $this->applyBelongsToManyPivotJoin(
                    statement: $statement,
                    pivotTable: $pivotTable,
                    pivotTargetColumns: $pivotTargetColumns,
                    targetTable: $targetTable,
                    targetPkColumns: $targetPkColumns,
                    pivotSourceColumns: $pivotSourceColumns,
                    parentPkColumns: $parentPkColumns,
                    parentValues: $parentValues,
                );

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            manager: $manager,
            modelClass: $relatedClass,
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function setupHasOneThroughRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $sourceProperty = PropertyReflector::createFromObject($model, $this->resolveSourceProperty($metaData, $relation));
        $sourceValue = $sourceProperty->getValue($model);

        if ($sourceValue === null) {
            if (!$relation->nullable) {
                throw ModelException::fromMissingForeignKeyValue(
                    modelClass: $metaData->model,
                    property: $relation->property,
                );
            }

            PropertyReflector::createFromObject($model, $relation->property)->setValue($model, null);

            return;
        }

        if (!\is_scalar($sourceValue)) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
            // @codeCoverageIgnoreEnd
        }

        $relatedClass = new \ReflectionClass($relation->relatedClass);
        $proxy = $relatedClass->newLazyProxy(
            fn (): object => $this->loadHasOneThroughRelation($metaData, $relation, $sourceValue),
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $proxy);
    }

    private function setupHasManyThroughRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $sourceProperty = PropertyReflector::createFromObject($model, $this->resolveSourceProperty($metaData, $relation));
        $sourceValue = $sourceProperty->getValue($model);
        $relatedClass = $relation->relatedClass;

        if ($sourceValue === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched(
                    values: [],
                    manager: $this->modelsManager,
                    modelClass: $relatedClass,
                ),
            );

            return;
        }

        if (!\is_scalar($sourceValue)) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var HasManyThrough $attribute */
        $attribute = $relation->attribute;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        $throughTable = $manager->metaData->getModel($attribute->through)->table;
        $throughSecondLocalKey = $this->resolveThroughSecondLocalKeyColumn($attribute);
        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $secondKey = $attribute->secondKey;
        $firstKey = $attribute->firstKey;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($manager, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement->whereIn(
                        column: $secondKey,
                        values: $manager->connection->select($throughTable)
                            ->select($throughSecondLocalKey)
                            ->where($firstKey, $sourceValue),
                    );

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: static fn (array $criteria): int => $manager->count(
                $relatedClass,
                static function (CountStatementInterface $statement) use ($manager, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue, $criteria): void {
                    $statement->whereIn(
                        column: $secondKey,
                        values: $manager->connection->select($throughTable)
                            ->select($throughSecondLocalKey)
                            ->where($firstKey, $sourceValue),
                    );

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }
                },
            ),
            manager: $manager,
            modelClass: $relatedClass,
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function setupMorphToRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        MorphToRelationMetaDataInterface $morphTo,
    ): void {
        $typeColumnProperty = $this->findPropertyByColumn(
            metaData: $metaData,
            column: $morphTo->typeColumn,
            relationProperty: $morphTo->property,
        );

        $idColumns = $morphTo->idColumns;
        $idColumnProperties = [];

        foreach ($idColumns as $idColumn) {
            $idColumnProperties[$idColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $idColumn,
                relationProperty: $morphTo->property,
            );
        }

        $typeValue = PropertyReflector::createFromObject($model, $typeColumnProperty)->getValue($model);
        $idTuple = $this->readTupleFromModel($model, $metaData, $idColumns, $idColumnProperties);
        $morphProperty = PropertyReflector::createFromObject($model, $morphTo->property);

        if ($typeValue === null || $typeValue === '' || $idTuple === null) {
            if (!$morphTo->nullable) {
                throw ModelException::fromMissingForeignKeyValue(
                    modelClass: $metaData->model,
                    property: $morphTo->property,
                );
            }

            $morphProperty->setValue($model, null);

            return;
        }

        if (!\is_string($typeValue)) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $typeColumnProperty,
                actualType: \get_debug_type($typeValue),
            );
            // @codeCoverageIgnoreEnd
        }

        $targetClass = MorphTypeResolver::resolve(
            typeValue: $typeValue,
            typeMap: $morphTo->typeMap,
        );

        if ($targetClass === null) {
            throw ModelException::fromMorphTypeValueUnresolvable(
                modelClass: $metaData->model,
                property: $morphTo->property,
                typeValue: $typeValue,
            );
        }

        $reflectionClass = new \ReflectionClass($targetClass);
        $manager = $this->modelsManager;
        $morphPropertyName = $morphTo->property;
        $sourceMetaData = $metaData;
        $idValues = \array_values($idTuple);

        $proxy = $reflectionClass->newLazyProxy(
            function () use ($manager, $targetClass, $idValues, $sourceMetaData, $morphPropertyName): object {
                $targetMetaData = $manager->metaData->getModel($targetClass);
                $targetPkColumns = $this->resolvePkColumns($targetMetaData);

                if (\sizeof($targetPkColumns) !== \sizeof($idValues)) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromRelationForeignKeyArityMismatch(
                        modelClass: $sourceMetaData->model,
                        property: $morphPropertyName,
                        keyKind: 'idColumn',
                        expected: \sizeof($targetPkColumns),
                        actual: \sizeof($idValues),
                    );
                    // @codeCoverageIgnoreEnd
                }

                $result = $manager->findFirst(
                    $targetClass,
                    static function (WhereStatementInterface $statement) use ($targetPkColumns, $idValues): void {
                        foreach ($targetPkColumns as $index => $pkColumn) {
                            $statement->where($pkColumn, $idValues[$index]);
                        }
                    },
                );

                if ($result === null) {
                    throw ModelException::fromMissingRelatedRecord(
                        modelClass: $sourceMetaData->model,
                        property: $morphPropertyName,
                        relatedClass: $targetClass,
                    );
                }

                return $result;
            },
        );

        $morphProperty->setValue($model, $proxy);
    }

    private function setupMorphOneRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        /** @var MorphOne $attribute */
        $attribute = $relation->attribute;
        $sourceColumns = $relation->referencedKeyColumns;
        $idColumns = $relation->foreignKeyColumns;

        if ($sourceColumns === null || $idColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $sourceTuple = $this->readTupleFromModel($model, $metaData, $sourceColumns, $sourcePropertyNames);

        if ($sourceTuple === null) {
            if (!$relation->nullable) {
                throw ModelException::fromMissingForeignKeyValue(
                    modelClass: $metaData->model,
                    property: $relation->property,
                );
            }

            PropertyReflector::createFromObject($model, $relation->property)->setValue($model, null);

            return;
        }

        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $reflectionClass = new \ReflectionClass($relation->relatedClass);
        $manager = $this->modelsManager;
        $relatedClass = $relation->relatedClass;
        $typeColumn = $attribute->typeColumn;
        $sourceMetaData = $metaData;
        $relationProperty = $relation->property;
        $sourceValues = \array_values($sourceTuple);

        $proxy = $reflectionClass->newLazyProxy(
            function () use ($manager, $relatedClass, $typeColumn, $idColumns, $typeValue, $sourceValues, $sourceMetaData, $relationProperty): object {
                $result = $manager->findFirst(
                    $relatedClass,
                    static function (WhereStatementInterface $statement) use ($typeColumn, $idColumns, $typeValue, $sourceValues): void {
                        $statement->where($typeColumn, $typeValue);

                        foreach ($idColumns as $index => $idColumn) {
                            $statement->where($idColumn, $sourceValues[$index]);
                        }
                    },
                );

                if ($result === null) {
                    throw ModelException::fromMissingRelatedRecord(
                        modelClass: $sourceMetaData->model,
                        property: $relationProperty,
                        relatedClass: $relatedClass,
                    );
                }

                return $result;
            },
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $proxy);
    }

    private function setupMorphManyRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        /** @var MorphMany $attribute */
        $attribute = $relation->attribute;
        $relatedClass = $relation->relatedClass;
        $sourceColumns = $relation->referencedKeyColumns;
        $idColumns = $relation->foreignKeyColumns;

        if ($sourceColumns === null || $idColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $sourceTuple = $this->readTupleFromModel($model, $metaData, $sourceColumns, $sourcePropertyNames);

        if ($sourceTuple === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched(
                    values: [],
                    manager: $this->modelsManager,
                    modelClass: $relatedClass,
                ),
            );

            return;
        }

        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $manager = $this->modelsManager;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;
        $typeColumn = $attribute->typeColumn;
        $sourceValues = \array_values($sourceTuple);

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($typeColumn, $idColumns, $typeValue, $sourceValues, $criteria, $orderBy, $limit, $offset): void {
                    $statement->where($typeColumn, $typeValue);

                    foreach ($idColumns as $index => $idColumn) {
                        $statement->where($idColumn, $sourceValues[$index]);
                    }

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $typeColumn, $idColumns, $typeValue, $sourceValues): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($typeColumn, $typeValue);

                foreach ($idColumns as $index => $idColumn) {
                    $statement->where($idColumn, $sourceValues[$index]);
                }

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            manager: $manager,
            modelClass: $relatedClass,
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function setupMorphToManyRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        $parentPkColumns = $this->resolvePkColumns($metaData);
        $targetPkColumns = $this->resolvePkColumns($targetMetaData);
        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyNames = [];

        foreach ($parentPkColumns as $parentColumn) {
            $sourcePropertyNames[$parentColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $parentColumn,
                relationProperty: $relation->property,
            );
        }

        $parentValues = $this->readTupleFromModel($model, $metaData, $parentPkColumns, $sourcePropertyNames);

        if ($parentValues === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched(
                    values: [],
                    manager: $this->modelsManager,
                    modelClass: $relatedClass,
                ),
            );

            return;
        }

        /** @var MorphToMany $attribute */
        $attribute = $relation->attribute;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $targetTable = $targetMetaData->table;
        $pivotTable = $attribute->table;
        $pivotTypeColumn = $attribute->typeColumn;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                function (SelectStatementInterface $statement) use ($pivotTable, $pivotSourceColumns, $pivotTargetColumns, $pivotTypeColumn, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues, $typeValue, $criteria, $orderBy, $limit, $offset): void {
                    $this->applyBelongsToManyPivotJoin(
                        statement: $statement,
                        pivotTable: $pivotTable,
                        pivotTargetColumns: $pivotTargetColumns,
                        targetTable: $targetTable,
                        targetPkColumns: $targetPkColumns,
                        pivotSourceColumns: $pivotSourceColumns,
                        parentPkColumns: $parentPkColumns,
                        parentValues: $parentValues,
                    );

                    $statement->where($pivotTable . '.' . $pivotTypeColumn, $typeValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: function (array $criteria) use ($manager, $pivotTable, $pivotSourceColumns, $pivotTargetColumns, $pivotTypeColumn, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues, $typeValue): int {
                $statement = $manager->connection->count($targetTable);

                $this->applyBelongsToManyPivotJoin(
                    statement: $statement,
                    pivotTable: $pivotTable,
                    pivotTargetColumns: $pivotTargetColumns,
                    targetTable: $targetTable,
                    targetPkColumns: $targetPkColumns,
                    pivotSourceColumns: $pivotSourceColumns,
                    parentPkColumns: $parentPkColumns,
                    parentValues: $parentValues,
                );

                $statement->where($pivotTable . '.' . $pivotTypeColumn, $typeValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            manager: $manager,
            modelClass: $relatedClass,
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function loadHasOneThroughRelation(
        ModelMetaDataInterface $sourceMetaData,
        ModelRelationInterface $relation,
        string|int|float|bool $sourceValue,
    ): object {
        /** @var HasOneThrough $attribute */
        $attribute = $relation->attribute;
        $manager = $this->modelsManager;
        $throughTable = $manager->metaData->getModel($attribute->through)->table;
        $throughSecondLocalKey = $this->resolveThroughSecondLocalKeyColumn($attribute);
        $targetTable = $manager->metaData->getModel($relation->relatedClass)->table;
        $secondKey = $attribute->secondKey;
        $firstKey = $attribute->firstKey;

        $result = $manager->findFirst(
            $relation->relatedClass,
            static function (WhereStatementInterface $statement) use ($throughTable, $throughSecondLocalKey, $targetTable, $secondKey, $firstKey, $sourceValue): void {
                $statement
                    ->innerJoin($throughTable, $throughTable . '.' . $throughSecondLocalKey, $targetTable . '.' . $secondKey)
                    ->where($throughTable . '.' . $firstKey, $sourceValue);
            },
        );

        if ($result === null) {
            throw ModelException::fromMissingRelatedRecord(
                modelClass: $sourceMetaData->model,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        return $result;
    }

    private function resolveThroughSecondLocalKeyColumn(
        HasOneThrough|HasManyThrough $attribute,
    ): string {
        if ($attribute->secondLocalKey !== null) {
            return $attribute->secondLocalKey;
        }

        $throughMetaData = $this->modelsManager->metaData->getModel($attribute->through);

        if (!$throughMetaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $attribute->through,
            );
            // @codeCoverageIgnoreEnd
        }

        return $throughMetaData->key->column;
    }

    /**
     * @param non-empty-array<string, string|int|float|bool> $sourceValues
     */
    private function loadSingleRelation(
        ModelMetaDataInterface $sourceMetaData,
        ModelRelationInterface $relation,
        array $sourceValues,
    ): object {
        $sourceColumns = $this->resolveSourceColumns($sourceMetaData, $relation);
        $targetColumns = $this->resolveTargetColumns($relation);

        $result = $this->modelsManager->findFirst(
            $relation->relatedClass,
            static function (WhereStatementInterface $statement) use ($sourceColumns, $targetColumns, $sourceValues): void {
                foreach ($sourceColumns as $index => $sourceColumn) {
                    $targetColumn = $targetColumns[$index];
                    $statement->where($targetColumn, $sourceValues[$sourceColumn]);
                }
            },
        );

        if ($result === null) {
            throw ModelException::fromMissingRelatedRecord(
                modelClass: $sourceMetaData->model,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        return $result;
    }

    /**
     * @param object[] $parents
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null> $with
     */
    public function eagerLoad(
        array $parents,
        array $with,
    ): void {
        if (\sizeof($parents) === 0 || \sizeof($with) === 0) {
            return; // @codeCoverageIgnore
        }

        $firstParent = $parents[\array_key_first($parents)];
        $tree = $this->parseWithTree(
            with: $with,
            modelClass: $firstParent::class,
        );

        $this->eagerLoadTree($parents, $tree);
    }

    /**
     * @param object[] $parents
     * @param array<string, EagerLoadNode> $tree
     */
    private function eagerLoadTree(
        array $parents,
        array $tree,
    ): void {
        if (\sizeof($parents) === 0 || \sizeof($tree) === 0) {
            return; // @codeCoverageIgnore
        }

        $firstParent = $parents[\array_key_first($parents)];
        $metaData = $this->modelsManager->metaData->getModel($firstParent::class);

        foreach ($tree as $relationName => $node) {
            $morphTo = $this->findMorphToByName($metaData, $relationName);

            if ($morphTo !== null) {
                $this->eagerLoadMorphTo($parents, $metaData, $morphTo, $node->constraint);

                if (\sizeof($node->children) > 0) {
                    $childrenByClass = $this->collectLoadedMorphToChildren($parents, $morphTo);

                    foreach ($childrenByClass as $group) {
                        if (\sizeof($group) === 0) {
                            continue; // @codeCoverageIgnore
                        }

                        $this->eagerLoadTree($group, $node->children);
                    }
                }

                continue;
            }

            $relation = $this->findRelationByName($metaData, $relationName);
            $attribute = $relation->attribute;
            $shaped = $this->shapeConstraint($node->constraint, $relation->relatedClass);

            if ($attribute instanceof HasMany) {
                $this->eagerLoadHasMany($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof BelongsToMany) {
                $this->eagerLoadBelongsToMany($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof HasManyThrough) {
                $this->eagerLoadHasManyThrough($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof HasOne) {
                $this->eagerLoadHasOne($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof BelongsTo) {
                $this->eagerLoadBelongsTo($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof HasOneThrough) {
                $this->eagerLoadHasOneThrough($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof MorphOne) {
                $this->eagerLoadMorphOne($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof MorphMany) {
                $this->eagerLoadMorphMany($parents, $metaData, $relation, $shaped);
            } elseif ($attribute instanceof MorphToMany) {
                $this->eagerLoadMorphToMany($parents, $metaData, $relation, $shaped);
            } else {
                // @codeCoverageIgnoreStart
                throw ModelException::fromEagerLoadingNotYetSupported(
                    attributeClass: $attribute::class,
                );
                // @codeCoverageIgnoreEnd
            }

            if (\sizeof($node->children) > 0) {
                $childrenByClass = $this->collectLoadedChildren($parents, $relation);

                foreach ($childrenByClass as $group) {
                    if (\sizeof($group) === 0) {
                        continue; // @codeCoverageIgnore
                    }

                    $this->eagerLoadTree($group, $node->children);
                }
            }
        }
    }

    /**
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null> $with
     * @param class-string $modelClass
     * @return array<string, EagerLoadNode>
     */
    private function parseWithTree(
        array $with,
        string $modelClass,
    ): array {
        /** @var array<string, EagerLoadNode> $tree */
        $tree = [];

        foreach ($with as $path => $constraint) {
            $segments = \explode('.', $path);

            foreach ($segments as $segment) {
                if ($segment === '') {
                    throw ModelException::fromInvalidEagerLoadPath(
                        modelClass: $modelClass,
                        path: $path,
                    );
                }
            }

            $cursor = &$tree;
            $leaf = null;

            foreach ($segments as $segment) {
                if (!isset($cursor[$segment])) {
                    $cursor[$segment] = new EagerLoadNode();
                }

                $leaf = $cursor[$segment];
                $cursor = &$leaf->children;
            }

            /** @var EagerLoadNode $leaf */
            $leaf->constraint = $constraint;
        }

        return $tree;
    }

    /**
     * @param object[] $parents
     * @param (\Closure(Relation<object>): Relation<object>)|null $constraint
     */
    private function eagerLoadMorphTo(
        array $parents,
        ModelMetaDataInterface $metaData,
        MorphToRelationMetaDataInterface $morphTo,
        ?\Closure $constraint,
    ): void {
        $typeColumnProperty = $this->findPropertyByColumn(
            metaData: $metaData,
            column: $morphTo->typeColumn,
            relationProperty: $morphTo->property,
        );

        $idColumns = $morphTo->idColumns;
        $idColumnProperties = [];

        foreach ($idColumns as $idColumn) {
            $idColumnProperties[$idColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $idColumn,
                relationProperty: $morphTo->property,
            );
        }

        /** @var array<string, array<string, non-empty-array<string, int|string>>> $tuplesByTypeAndHash */
        $tuplesByTypeAndHash = [];

        foreach ($parents as $parent) {
            $typeValue = PropertyReflector::createFromObject($parent, $typeColumnProperty)->getValue($parent);
            $idTuple = $this->readTupleFromModel($parent, $metaData, $idColumns, $idColumnProperties);

            if ($typeValue === null || $typeValue === '' || $idTuple === null) {
                continue;
            }

            if (!\is_string($typeValue)) {
                continue; // @codeCoverageIgnore
            }

            $tuplesByTypeAndHash[$typeValue][RelationKeyTupleHasher::hash(\array_values($idTuple))] = $idTuple;
        }

        /** @var array<string, array<string, object>> $targetsByTypeAndHash */
        $targetsByTypeAndHash = [];
        $manager = $this->modelsManager;

        foreach ($tuplesByTypeAndHash as $typeValue => $tuplesByHash) {
            $targetClass = MorphTypeResolver::resolve(
                typeValue: $typeValue,
                typeMap: $morphTo->typeMap,
            );

            if ($targetClass === null) {
                throw ModelException::fromMorphTypeValueUnresolvable(
                    modelClass: $metaData->model,
                    property: $morphTo->property,
                    typeValue: $typeValue,
                );
            }

            $targetMetaData = $manager->metaData->getModel($targetClass);
            $targetPkColumns = $this->resolvePkColumns($targetMetaData);

            if (\sizeof($targetPkColumns) !== \sizeof($idColumns)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromRelationForeignKeyArityMismatch(
                    modelClass: $metaData->model,
                    property: $morphTo->property,
                    keyKind: 'idColumn',
                    expected: \sizeof($targetPkColumns),
                    actual: \sizeof($idColumns),
                );
                // @codeCoverageIgnoreEnd
            }

            $targetPropertyNames = [];

            foreach ($targetPkColumns as $pkColumn) {
                $targetPropertyNames[$pkColumn] = $this->findPropertyByColumn(
                    metaData: $targetMetaData,
                    column: $pkColumn,
                    relationProperty: $morphTo->property,
                );
            }

            if ($tuplesByHash === []) {
                continue; // @codeCoverageIgnore
            }

            $targetQuery = $manager->connection->select($targetMetaData->table);
            $this->applyCompositeTuplesFilter($targetQuery, $idColumns, $targetPkColumns, $tuplesByHash);

            $shaped = $this->shapeConstraint($constraint, $targetClass);
            $this->applyConstraintToBatch($targetQuery, $shaped);

            $rows = $targetQuery->fetchAll($targetClass, $manager->hydrator);

            foreach ($rows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $idColumns, $targetPkColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByTypeAndHash[$typeValue][RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
            }
        }

        foreach ($parents as $parent) {
            $typeValue = PropertyReflector::createFromObject($parent, $typeColumnProperty)->getValue($parent);
            $idTuple = $this->readTupleFromModel($parent, $metaData, $idColumns, $idColumnProperties);
            $morphProperty = PropertyReflector::createFromObject($parent, $morphTo->property);

            if ($typeValue === null || $typeValue === '' || $idTuple === null) {
                if (!$morphTo->nullable) {
                    throw ModelException::fromMissingForeignKeyValue(
                        modelClass: $metaData->model,
                        property: $morphTo->property,
                    );
                }

                $morphProperty->setValue($parent, null);

                continue;
            }

            if (!\is_string($typeValue)) {
                continue; // @codeCoverageIgnore
            }

            $target = $targetsByTypeAndHash[$typeValue][RelationKeyTupleHasher::hash(\array_values($idTuple))] ?? null;

            if ($target === null) {
                if ($morphTo->nullable) {
                    $morphProperty->setValue($parent, null);

                    continue;
                }

                $targetClass = MorphTypeResolver::resolve(
                    typeValue: $typeValue,
                    typeMap: $morphTo->typeMap,
                ) ?? $typeValue;

                /** @var class-string $targetClass */
                throw ModelException::fromMissingRelatedRecord(
                    modelClass: $metaData->model,
                    property: $morphTo->property,
                    relatedClass: $targetClass,
                );
            }

            $morphProperty->setValue($parent, $target);
        }
    }

    /**
     * @param object[] $parents
     * @return array<class-string, list<object>>
     */
    private function collectLoadedMorphToChildren(
        array $parents,
        MorphToRelationMetaDataInterface $morphTo,
    ): array {
        /** @var array<class-string, list<object>> $grouped */
        $grouped = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $morphTo->property)->getValue($parent);

            if (!\is_object($value)) {
                continue;
            }

            $grouped[$value::class][] = $value;
        }

        return $grouped;
    }

    private function findMorphToByName(
        ModelMetaDataInterface $metaData,
        string $relationName,
    ): ?MorphToRelationMetaDataInterface {
        foreach ($metaData->morphToRelations as $morphTo) {
            if ($morphTo->property === $relationName) {
                return $morphTo;
            }
        }

        return null;
    }

    /**
     * @param object[] $parents
     * @return array<class-string, list<object>>
     */
    private function collectLoadedChildren(
        array $parents,
        ModelRelationInterface $relation,
    ): array {
        /** @var array<class-string, list<object>> $grouped */
        $grouped = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $relation->property)->getValue($parent);

            if ($value === null) {
                continue;
            }

            if ($value instanceof Relation) {
                /** @var object $child */
                foreach ($value as $child) {
                    $grouped[$child::class][] = $child;
                }

                continue;
            }

            if (!\is_object($value)) {
                continue; // @codeCoverageIgnore
            }

            $grouped[$value::class][] = $value;
        }

        return $grouped;
    }

    /**
     * @param (\Closure(Relation<object>): Relation<object>)|null $constraint
     * @param class-string $relatedClass
     * @return Relation<object>|null
     */
    private function shapeConstraint(
        ?\Closure $constraint,
        string $relatedClass,
    ): ?Relation {
        if ($constraint === null) {
            return null;
        }

        $scratch = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            manager: $this->modelsManager,
            modelClass: $relatedClass,
        );

        return $constraint($scratch);
    }

    /**
     * @param Relation<object>|null $shaped
     */
    private function applyConstraintToBatch(
        WhereStatementInterface $statement,
        ?Relation $shaped,
    ): void {
        if ($shaped === null) {
            return;
        }

        foreach ($shaped->criteriaStack as $criterion) {
            $criterion($statement);
        }
    }

    /**
     * @template TItem of object
     *
     * @param list<TItem> $rows
     * @param Relation<object>|null $shaped
     * @return list<TItem>
     */
    private function sliceForConstraint(
        array $rows,
        ?Relation $shaped,
    ): array {
        if ($shaped === null || $shaped->limit === null) {
            return $rows;
        }

        return \array_slice($rows, $shaped->offset ?? 0, $shaped->limit);
    }

    private function findRelationByName(
        ModelMetaDataInterface $metaData,
        string $relationName,
    ): ModelRelationInterface {
        foreach ($metaData->relations as $relation) {
            if ($relation->property === $relationName) {
                return $relation;
            }
        }

        throw ModelException::fromUnknownEagerLoadRelation(
            modelClass: $metaData->model,
            relationName: $relationName,
        );
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadHasMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        $sourceColumns = $this->resolveSourceColumns($metaData, $relation);
        $targetColumns = $this->resolveTargetColumns($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($targetColumns as $targetColumn) {
            $targetPropertyNames[$targetColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $targetColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $tuplesByHash */
        $tuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue;
            }

            $tuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, list<object>> $grouped */
        $grouped = [];

        if (\sizeof($tuplesByHash) > 0) {
            $batchQuery = $manager->connection->select($targetTable);

            $this->applyCompositeTuplesFilter($batchQuery, $sourceColumns, $targetColumns, $tuplesByHash);
            $this->applyConstraintToBatch($batchQuery, $shaped);

            $batchRows = $batchQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($batchRows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $sourceColumns, $targetColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $grouped[RelationKeyTupleHasher::hash(\array_values($rowTuple))][] = $row;
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);
            $prefetched = $tuple === null
                ? []
                : ($grouped[RelationKeyTupleHasher::hash(\array_values($tuple))] ?? []);
            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildHasManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                sourceColumns: $sourceColumns,
                targetColumns: $targetColumns,
                sourceValues: $tuple,
                prefetched: $prefetched,
                shaped: $shaped,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param non-empty-list<string> $sourceColumns
     * @param non-empty-list<string> $targetColumns
     * @param non-empty-array<string, int|string>|null $sourceValues
     * @param list<object> $prefetched
     * @param Relation<object>|null $shaped
     * @return Relation<object>
     */
    private function buildHasManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        array $sourceColumns,
        array $targetColumns,
        ?array $sourceValues,
        array $prefetched,
        ?Relation $shaped = null,
    ): Relation {
        if ($sourceValues === null) {
            // @codeCoverageIgnoreStart
            return Relation::createFromPrefetched(
                values: $prefetched,
                manager: $manager,
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($sourceColumns, $targetColumns, $sourceValues, $criteria, $orderBy, $limit, $offset): void {
                    foreach ($sourceColumns as $index => $sourceColumn) {
                        $targetColumn = $targetColumns[$index];
                        $statement->where($targetColumn, $sourceValues[$sourceColumn]);
                    }

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $sourceColumns, $targetColumns, $sourceValues): int {
                $statement = $manager->connection->count($targetTable);

                foreach ($sourceColumns as $index => $sourceColumn) {
                    $targetColumn = $targetColumns[$index];
                    $statement->where($targetColumn, $sourceValues[$sourceColumn]);
                }

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            initialCriteriaStack: $shaped->criteriaStack ?? [],
            initialOrderBy: $shaped->orderBy ?? [],
            initialLimit: $shaped?->limit,
            initialOffset: $shaped?->offset,
            manager: $manager,
            modelClass: $relatedClass,
        );
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadBelongsToMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var BelongsToMany $attribute */
        $attribute = $relation->attribute;

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        $parentPkColumns = $this->resolvePkColumns($metaData);
        $targetPkColumns = $this->resolvePkColumns($targetMetaData);
        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $targetTable = $targetMetaData->table;
        $pivotTable = $attribute->table;

        $sourcePropertyNames = [];

        foreach ($parentPkColumns as $parentColumn) {
            $sourcePropertyNames[$parentColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $parentColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($targetPkColumns as $targetColumn) {
            $targetPropertyNames[$targetColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $targetColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $parentTuplesByHash */
        $parentTuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $parentPkColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue; // @codeCoverageIgnore
            }

            $parentTuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, list<string>> $targetHashesByParent */
        $targetHashesByParent = [];
        /** @var array<string, non-empty-array<string, int|string>> $targetTuplesByHash */
        $targetTuplesByHash = [];
        /** @var array<string, object> $targetsByHash */
        $targetsByHash = [];

        if (\sizeof($parentTuplesByHash) > 0) {
            $pivotQuery = $manager->connection
                ->select($pivotTable)
                ->select(...$pivotSourceColumns, ...$pivotTargetColumns);

            $this->applyCompositeTuplesFilter($pivotQuery, $parentPkColumns, $pivotSourceColumns, $parentTuplesByHash);

            $pivotResult = $pivotQuery->execute();

            foreach ($pivotResult as $row) {
                /** @var non-empty-array<string, int|string> $parentTuple */
                $parentTuple = [];
                /** @var non-empty-array<string, int|string> $targetTuple */
                $targetTuple = [];

                foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                    $value = $row->properties[$pivotSourceColumn] ?? null;

                    if (!\is_int($value) && !\is_string($value)) {
                        continue 2; // @codeCoverageIgnore
                    }

                    $parentTuple[$parentPkColumns[$index]] = $value;
                }

                foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                    $value = $row->properties[$pivotTargetColumn] ?? null;

                    if (!\is_int($value) && !\is_string($value)) {
                        continue 2; // @codeCoverageIgnore
                    }

                    $targetTuple[$targetPkColumns[$index]] = $value;
                }

                $parentHash = RelationKeyTupleHasher::hash(\array_values($parentTuple));
                $targetHash = RelationKeyTupleHasher::hash(\array_values($targetTuple));

                $targetHashesByParent[$parentHash][] = $targetHash;
                $targetTuplesByHash[$targetHash] = $targetTuple;
            }

            if (\sizeof($targetTuplesByHash) > 0) {
                $targetQuery = $manager->connection->select($targetTable);

                $this->applyCompositeTuplesFilter($targetQuery, $targetPkColumns, $targetPkColumns, $targetTuplesByHash);
                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $targetPkColumns, $targetPkColumns);

                    if ($rowTuple === null) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByHash[RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $parentPkColumns, $sourcePropertyNames);
            /** @var list<object> $prefetched */
            $prefetched = [];

            if ($tuple !== null) {
                $parentHash = RelationKeyTupleHasher::hash(\array_values($tuple));

                foreach ($targetHashesByParent[$parentHash] ?? [] as $targetHash) {
                    if (isset($targetsByHash[$targetHash])) {
                        $prefetched[] = $targetsByHash[$targetHash];
                    }
                }
            }

            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildBelongsToManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetPkColumns: $targetPkColumns,
                pivotTable: $pivotTable,
                pivotSourceColumns: $pivotSourceColumns,
                pivotTargetColumns: $pivotTargetColumns,
                parentPkColumns: $parentPkColumns,
                parentValues: $tuple,
                prefetched: $prefetched,
                shaped: $shaped,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param non-empty-list<string> $targetPkColumns
     * @param non-empty-list<string> $pivotSourceColumns
     * @param non-empty-list<string> $pivotTargetColumns
     * @param non-empty-list<string> $parentPkColumns
     * @param non-empty-array<string, int|string>|null $parentValues
     * @param list<object> $prefetched
     * @param Relation<object>|null $shaped
     * @return Relation<object>
     */
    private function buildBelongsToManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        array $targetPkColumns,
        string $pivotTable,
        array $pivotSourceColumns,
        array $pivotTargetColumns,
        array $parentPkColumns,
        ?array $parentValues,
        array $prefetched,
        ?Relation $shaped = null,
    ): Relation {
        if ($parentValues === null) {
            // @codeCoverageIgnoreStart
            return Relation::createFromPrefetched(
                values: $prefetched,
                manager: $manager,
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                function (SelectStatementInterface $statement) use ($pivotTable, $pivotSourceColumns, $pivotTargetColumns, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues, $criteria, $orderBy, $limit, $offset): void {
                    $this->applyBelongsToManyPivotJoin(
                        statement: $statement,
                        pivotTable: $pivotTable,
                        pivotTargetColumns: $pivotTargetColumns,
                        targetTable: $targetTable,
                        targetPkColumns: $targetPkColumns,
                        pivotSourceColumns: $pivotSourceColumns,
                        parentPkColumns: $parentPkColumns,
                        parentValues: $parentValues,
                    );

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: function (array $criteria) use ($manager, $pivotTable, $pivotSourceColumns, $pivotTargetColumns, $parentPkColumns, $targetPkColumns, $targetTable, $parentValues): int {
                $statement = $manager->connection->count($targetTable);

                $this->applyBelongsToManyPivotJoin(
                    statement: $statement,
                    pivotTable: $pivotTable,
                    pivotTargetColumns: $pivotTargetColumns,
                    targetTable: $targetTable,
                    targetPkColumns: $targetPkColumns,
                    pivotSourceColumns: $pivotSourceColumns,
                    parentPkColumns: $parentPkColumns,
                    parentValues: $parentValues,
                );

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            initialCriteriaStack: $shaped->criteriaStack ?? [],
            initialOrderBy: $shaped->orderBy ?? [],
            initialLimit: $shaped?->limit,
            initialOffset: $shaped?->offset,
            manager: $manager,
            modelClass: $relatedClass,
        );
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadHasManyThrough(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var HasManyThrough $attribute */
        $attribute = $relation->attribute;
        $sourcePropertyName = $this->resolveSourceProperty($metaData, $relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $throughTable = $manager->metaData->getModel($attribute->through)->table;
        $throughSecondLocalKey = $this->resolveThroughSecondLocalKeyColumn($attribute);
        $targetTable = $targetMetaData->table;
        $secondKey = $attribute->secondKey;
        $firstKey = $attribute->firstKey;

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        $targetPrimaryKey = $targetMetaData->key->column;
        $targetForeignProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $secondKey,
            relationProperty: $relation->property,
        );

        /** @var array<int|string, int|string> $sourceValues */
        $sourceValues = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);

            if ($value === null) {
                continue;
            }

            if (!\is_int($value) && !\is_string($value)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
                // @codeCoverageIgnoreEnd
            }

            $sourceValues[$value] = $value;
        }

        /** @var array<int|string, list<int|string>> $throughPairs */
        $throughPairs = [];
        /** @var array<int|string, list<object>> $targetsByFk */
        $targetsByFk = [];

        if (\sizeof($sourceValues) > 0) {
            $throughResult = $manager->connection
                ->select($throughTable)
                ->select($firstKey, $throughSecondLocalKey)
                ->distinct()
                ->whereIn($firstKey, \array_values($sourceValues))
                ->execute();

            /** @var array<int|string, int|string> $targetForeignValues */
            $targetForeignValues = [];

            foreach ($throughResult as $row) {
                $first = $row->properties[$firstKey] ?? null;
                $second = $row->properties[$throughSecondLocalKey] ?? null;

                if (!\is_int($first) && !\is_string($first)) {
                    continue; // @codeCoverageIgnore
                }

                if (!\is_int($second) && !\is_string($second)) {
                    continue; // @codeCoverageIgnore
                }

                $throughPairs[$first][] = $second;
                $targetForeignValues[$second] = $second;
            }

            if (\sizeof($targetForeignValues) > 0) {
                $targetQuery = $manager->connection
                    ->select($targetTable)
                    ->whereIn($secondKey, \array_values($targetForeignValues));

                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $fk = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                    if (!\is_int($fk) && !\is_string($fk)) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByFk[$fk][] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            /** @var list<object> $prefetched */
            $prefetched = [];

            if (\is_int($sourceValue) || \is_string($sourceValue)) {
                foreach ($throughPairs[$sourceValue] ?? [] as $secondValue) {
                    foreach ($targetsByFk[$secondValue] ?? [] as $targetRow) {
                        $prefetched[] = $targetRow;
                    }
                }
            }

            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildHasManyThroughEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetPrimaryKey: $targetPrimaryKey,
                throughTable: $throughTable,
                throughSecondLocalKey: $throughSecondLocalKey,
                secondKey: $secondKey,
                firstKey: $firstKey,
                sourceValue: $sourceValue,
                prefetched: $prefetched,
                shaped: $shaped,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param list<object> $prefetched
     * @param Relation<object>|null $shaped
     * @return Relation<object>
     */
    private function buildHasManyThroughEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $targetPrimaryKey,
        string $throughTable,
        string $throughSecondLocalKey,
        string $secondKey,
        string $firstKey,
        mixed $sourceValue,
        array $prefetched,
        ?Relation $shaped = null,
    ): Relation {
        if (!\is_int($sourceValue) && !\is_string($sourceValue)) {
            // @codeCoverageIgnoreStart
            return Relation::createFromPrefetched(
                values: $prefetched,
                manager: $manager,
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static function (array $criteria, array $orderBy, ?int $limit, ?int $offset) use ($manager, $targetTable, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue, $relatedClass): iterable {
                $statement = $manager->connection->select($targetTable)
                    ->whereIn(
                        column: $secondKey,
                        values: $manager->connection->select($throughTable)
                            ->select($throughSecondLocalKey)
                            ->where($firstKey, $sourceValue),
                    );

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                foreach ($orderBy as $spec) {
                    $statement->orderBy($spec['column'], $spec['direction']);
                }

                if ($limit !== null) {
                    $statement->limit($limit, $offset);
                }

                return $statement->fetchAll($relatedClass, $manager->hydrator);
            },
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->whereIn(
                        column: $secondKey,
                        values: $manager->connection->select($throughTable)
                            ->select($throughSecondLocalKey)
                            ->where($firstKey, $sourceValue),
                    );

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            initialCriteriaStack: $shaped->criteriaStack ?? [],
            initialOrderBy: $shaped->orderBy ?? [],
            initialLimit: $shaped?->limit,
            initialOffset: $shaped?->offset,
            manager: $manager,
            modelClass: $relatedClass,
        );
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadHasOne(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        $sourceColumns = $this->resolveSourceColumns($metaData, $relation);
        $targetColumns = $this->resolveTargetColumns($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($targetColumns as $targetColumn) {
            $targetPropertyNames[$targetColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $targetColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $tuplesByHash */
        $tuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue;
            }

            $tuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, object> $targetsByHash */
        $targetsByHash = [];

        if (\sizeof($tuplesByHash) > 0) {
            $targetQuery = $manager->connection->select($targetTable);

            $this->applyCompositeTuplesFilter($targetQuery, $sourceColumns, $targetColumns, $tuplesByHash);
            $this->applyConstraintToBatch($targetQuery, $shaped);

            $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($targetRows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $sourceColumns, $targetColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByHash[RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);
            $target = $tuple === null
                ? null
                : ($targetsByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] ?? null);

            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $tuple === null ? null : \array_values($tuple)[0],
                target: $target,
            );
        }
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadBelongsTo(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        $sourceColumns = $this->resolveSourceColumns($metaData, $relation);
        $targetColumns = $this->resolveTargetColumns($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($targetColumns as $targetColumn) {
            $targetPropertyNames[$targetColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $targetColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $tuplesByHash */
        $tuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue;
            }

            $tuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, object> $targetsByHash */
        $targetsByHash = [];

        if (\sizeof($tuplesByHash) > 0) {
            $targetQuery = $manager->connection->select($targetTable);

            $this->applyCompositeTuplesFilter($targetQuery, $sourceColumns, $targetColumns, $tuplesByHash);
            $this->applyConstraintToBatch($targetQuery, $shaped);

            $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($targetRows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $sourceColumns, $targetColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByHash[RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);
            $target = $tuple === null
                ? null
                : ($targetsByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] ?? null);

            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $tuple === null ? null : \array_values($tuple)[0],
                target: $target,
            );
        }
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadHasOneThrough(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var HasOneThrough $attribute */
        $attribute = $relation->attribute;
        $sourcePropertyName = $this->resolveSourceProperty($metaData, $relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $throughTable = $manager->metaData->getModel($attribute->through)->table;
        $throughSecondLocalKey = $this->resolveThroughSecondLocalKeyColumn($attribute);
        $targetTable = $targetMetaData->table;
        $secondKey = $attribute->secondKey;
        $firstKey = $attribute->firstKey;

        $targetForeignProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $secondKey,
            relationProperty: $relation->property,
        );

        /** @var array<int|string, int|string> $sourceValues */
        $sourceValues = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);

            if ($value === null) {
                continue;
            }

            if (!\is_int($value) && !\is_string($value)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
                // @codeCoverageIgnoreEnd
            }

            $sourceValues[$value] = $value;
        }

        /** @var array<int|string, int|string> $firstToSecond */
        $firstToSecond = [];

        /** @var array<int|string, object> $targetsByFk */
        $targetsByFk = [];

        if (\sizeof($sourceValues) > 0) {
            $throughResult = $manager->connection
                ->select($throughTable)
                ->select($firstKey, $throughSecondLocalKey)
                ->distinct()
                ->whereIn($firstKey, \array_values($sourceValues))
                ->execute();

            /** @var array<int|string, int|string> $targetForeignValues */
            $targetForeignValues = [];

            foreach ($throughResult as $row) {
                $first = $row->properties[$firstKey] ?? null;
                $second = $row->properties[$throughSecondLocalKey] ?? null;

                if (!\is_int($first) && !\is_string($first)) {
                    continue; // @codeCoverageIgnore
                }

                if (!\is_int($second) && !\is_string($second)) {
                    continue; // @codeCoverageIgnore
                }

                $firstToSecond[$first] = $second;
                $targetForeignValues[$second] = $second;
            }

            if (\sizeof($targetForeignValues) > 0) {
                $targetQuery = $manager->connection
                    ->select($targetTable)
                    ->whereIn($secondKey, \array_values($targetForeignValues));

                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $fk = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                    if (!\is_int($fk) && !\is_string($fk)) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByFk[$fk] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $target = null;

            if (\is_int($sourceValue) || \is_string($sourceValue)) {
                $secondValue = $firstToSecond[$sourceValue] ?? null;

                if ($secondValue !== null) {
                    $target = $targetsByFk[$secondValue] ?? null;
                }
            }

            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $sourceValue,
                target: $target,
            );
        }
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadMorphOne(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var MorphOne $attribute */
        $attribute = $relation->attribute;
        $sourceColumns = $relation->referencedKeyColumns;
        $idColumns = $relation->foreignKeyColumns;

        if ($sourceColumns === null || $idColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;
        $typeColumn = $attribute->typeColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($idColumns as $idColumn) {
            $targetPropertyNames[$idColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $idColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $tuplesByHash */
        $tuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue;
            }

            $tuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, object> $targetsByHash */
        $targetsByHash = [];

        if (\sizeof($tuplesByHash) > 0) {
            $targetQuery = $manager->connection
                ->select($targetTable)
                ->where($typeColumn, $typeValue);

            $this->applyCompositeTuplesFilter($targetQuery, $sourceColumns, $idColumns, $tuplesByHash);
            $this->applyConstraintToBatch($targetQuery, $shaped);

            $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($targetRows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $sourceColumns, $idColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByHash[RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);
            $target = $tuple === null
                ? null
                : ($targetsByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] ?? null);

            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $tuple === null ? null : \array_values($tuple)[0],
                target: $target,
            );
        }
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadMorphMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var MorphMany $attribute */
        $attribute = $relation->attribute;
        $sourceColumns = $relation->referencedKeyColumns;
        $idColumns = $relation->foreignKeyColumns;

        if ($sourceColumns === null || $idColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;
        $typeColumn = $attribute->typeColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );

        $sourcePropertyNames = [];

        foreach ($sourceColumns as $sourceColumn) {
            $sourcePropertyNames[$sourceColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $sourceColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($idColumns as $idColumn) {
            $targetPropertyNames[$idColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $idColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $tuplesByHash */
        $tuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue;
            }

            $tuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, list<object>> $grouped */
        $grouped = [];

        if (\sizeof($tuplesByHash) > 0) {
            $batchQuery = $manager->connection->select($targetTable)
                ->where($typeColumn, $typeValue);

            $this->applyCompositeTuplesFilter($batchQuery, $sourceColumns, $idColumns, $tuplesByHash);
            $this->applyConstraintToBatch($batchQuery, $shaped);

            $batchRows = $batchQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($batchRows as $row) {
                $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $sourceColumns, $idColumns);

                if ($rowTuple === null) {
                    continue; // @codeCoverageIgnore
                }

                $grouped[RelationKeyTupleHasher::hash(\array_values($rowTuple))][] = $row;
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $sourceColumns, $sourcePropertyNames);
            $prefetched = $tuple === null
                ? []
                : ($grouped[RelationKeyTupleHasher::hash(\array_values($tuple))] ?? []);
            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildMorphManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                typeColumn: $typeColumn,
                idColumns: $idColumns,
                typeValue: $typeValue,
                sourceValues: $tuple === null ? null : \array_values($tuple),
                prefetched: $prefetched,
                shaped: $shaped,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param non-empty-list<string> $idColumns
     * @param non-empty-list<int|string>|null $sourceValues
     * @param list<object> $prefetched
     * @param Relation<object>|null $shaped
     * @return Relation<object>
     */
    private function buildMorphManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $typeColumn,
        array $idColumns,
        string $typeValue,
        ?array $sourceValues,
        array $prefetched,
        ?Relation $shaped = null,
    ): Relation {
        if ($sourceValues === null) {
            // @codeCoverageIgnoreStart
            return Relation::createFromPrefetched(
                values: $prefetched,
                manager: $manager,
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($typeColumn, $idColumns, $typeValue, $sourceValues, $criteria, $orderBy, $limit, $offset): void {
                    $statement->where($typeColumn, $typeValue);

                    foreach ($idColumns as $index => $idColumn) {
                        $statement->where($idColumn, $sourceValues[$index]);
                    }

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $typeColumn, $idColumns, $typeValue, $sourceValues): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($typeColumn, $typeValue);

                foreach ($idColumns as $index => $idColumn) {
                    $statement->where($idColumn, $sourceValues[$index]);
                }

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            initialCriteriaStack: $shaped->criteriaStack ?? [],
            initialOrderBy: $shaped->orderBy ?? [],
            initialLimit: $shaped?->limit,
            initialOffset: $shaped?->offset,
            manager: $manager,
            modelClass: $relatedClass,
        );
    }

    /**
     * @param object[] $parents
     * @param Relation<object>|null $shaped
     */
    private function eagerLoadMorphToMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        ?Relation $shaped = null,
    ): void {
        /** @var MorphToMany $attribute */
        $attribute = $relation->attribute;

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        $parentPkColumns = $this->resolvePkColumns($metaData);
        $targetPkColumns = $this->resolvePkColumns($targetMetaData);
        $pivotSourceColumns = $relation->pivotSourceColumns;
        $pivotTargetColumns = $relation->pivotTargetColumns;

        if ($pivotSourceColumns === null || $pivotTargetColumns === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromRelationNotFoundOnModel(
                modelClass: $metaData->model,
                property: $relation->property,
            );
            // @codeCoverageIgnoreEnd
        }

        $targetTable = $targetMetaData->table;
        $pivotTable = $attribute->table;
        $pivotTypeColumn = $attribute->typeColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );

        $sourcePropertyNames = [];

        foreach ($parentPkColumns as $parentColumn) {
            $sourcePropertyNames[$parentColumn] = $this->findPropertyByColumn(
                metaData: $metaData,
                column: $parentColumn,
                relationProperty: $relation->property,
            );
        }

        $targetPropertyNames = [];

        foreach ($targetPkColumns as $targetColumn) {
            $targetPropertyNames[$targetColumn] = $this->findPropertyByColumn(
                metaData: $targetMetaData,
                column: $targetColumn,
                relationProperty: $relation->property,
            );
        }

        /** @var array<string, non-empty-array<string, int|string>> $parentTuplesByHash */
        $parentTuplesByHash = [];

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $parentPkColumns, $sourcePropertyNames);

            if ($tuple === null) {
                continue; // @codeCoverageIgnore
            }

            $parentTuplesByHash[RelationKeyTupleHasher::hash(\array_values($tuple))] = $tuple;
        }

        /** @var array<string, list<string>> $targetHashesByParent */
        $targetHashesByParent = [];
        /** @var array<string, non-empty-array<string, int|string>> $targetTuplesByHash */
        $targetTuplesByHash = [];
        /** @var array<string, object> $targetsByHash */
        $targetsByHash = [];

        if (\sizeof($parentTuplesByHash) > 0) {
            $pivotQuery = $manager->connection
                ->select($pivotTable)
                ->select(...$pivotSourceColumns, ...$pivotTargetColumns)
                ->where($pivotTypeColumn, $typeValue);

            $this->applyCompositeTuplesFilter($pivotQuery, $parentPkColumns, $pivotSourceColumns, $parentTuplesByHash);

            $pivotResult = $pivotQuery->execute();

            foreach ($pivotResult as $row) {
                /** @var non-empty-array<string, int|string> $parentTuple */
                $parentTuple = [];
                /** @var non-empty-array<string, int|string> $targetTuple */
                $targetTuple = [];

                foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
                    $value = $row->properties[$pivotSourceColumn] ?? null;

                    if (!\is_int($value) && !\is_string($value)) {
                        continue 2; // @codeCoverageIgnore
                    }

                    $parentTuple[$parentPkColumns[$index]] = $value;
                }

                foreach ($pivotTargetColumns as $index => $pivotTargetColumn) {
                    $value = $row->properties[$pivotTargetColumn] ?? null;

                    if (!\is_int($value) && !\is_string($value)) {
                        continue 2; // @codeCoverageIgnore
                    }

                    $targetTuple[$targetPkColumns[$index]] = $value;
                }

                $parentHash = RelationKeyTupleHasher::hash(\array_values($parentTuple));
                $targetHash = RelationKeyTupleHasher::hash(\array_values($targetTuple));

                $targetHashesByParent[$parentHash][] = $targetHash;
                $targetTuplesByHash[$targetHash] = $targetTuple;
            }

            if (\sizeof($targetTuplesByHash) > 0) {
                $targetQuery = $manager->connection->select($targetTable);

                $this->applyCompositeTuplesFilter($targetQuery, $targetPkColumns, $targetPkColumns, $targetTuplesByHash);
                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $rowTuple = $this->readTupleFromModelByColumnMap($row, $targetPropertyNames, $targetPkColumns, $targetPkColumns);

                    if ($rowTuple === null) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByHash[RelationKeyTupleHasher::hash(\array_values($rowTuple))] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $tuple = $this->readTupleFromModel($parent, $metaData, $parentPkColumns, $sourcePropertyNames);
            /** @var list<object> $prefetched */
            $prefetched = [];

            if ($tuple !== null) {
                $parentHash = RelationKeyTupleHasher::hash(\array_values($tuple));

                foreach ($targetHashesByParent[$parentHash] ?? [] as $targetHash) {
                    if (isset($targetsByHash[$targetHash])) {
                        $prefetched[] = $targetsByHash[$targetHash];
                    }
                }
            }

            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildMorphToManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetPkColumns: $targetPkColumns,
                pivotTable: $pivotTable,
                pivotTypeColumn: $pivotTypeColumn,
                pivotSourceColumns: $pivotSourceColumns,
                pivotTargetColumns: $pivotTargetColumns,
                parentPkColumns: $parentPkColumns,
                typeValue: $typeValue,
                parentValues: $tuple,
                prefetched: $prefetched,
                shaped: $shaped,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param non-empty-list<string> $targetPkColumns
     * @param non-empty-list<string> $pivotSourceColumns
     * @param non-empty-list<string> $pivotTargetColumns
     * @param non-empty-list<string> $parentPkColumns
     * @param non-empty-array<string, int|string>|null $parentValues
     * @param list<object> $prefetched
     * @param Relation<object>|null $shaped
     * @return Relation<object>
     */
    private function buildMorphToManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        array $targetPkColumns,
        string $pivotTable,
        string $pivotTypeColumn,
        array $pivotSourceColumns,
        array $pivotTargetColumns,
        array $parentPkColumns,
        string $typeValue,
        ?array $parentValues,
        array $prefetched,
        ?Relation $shaped = null,
    ): Relation {
        if ($parentValues === null) {
            // @codeCoverageIgnoreStart
            return Relation::createFromPrefetched(
                values: $prefetched,
                manager: $manager,
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                function (SelectStatementInterface $statement) use ($pivotTable, $pivotSourceColumns, $pivotTargetColumns, $pivotTypeColumn, $parentPkColumns, $targetPkColumns, $targetTable, $typeValue, $parentValues, $criteria, $orderBy, $limit, $offset): void {
                    $this->applyBelongsToManyPivotJoin(
                        statement: $statement,
                        pivotTable: $pivotTable,
                        pivotTargetColumns: $pivotTargetColumns,
                        targetTable: $targetTable,
                        targetPkColumns: $targetPkColumns,
                        pivotSourceColumns: $pivotSourceColumns,
                        parentPkColumns: $parentPkColumns,
                        parentValues: $parentValues,
                    );

                    $statement->where($pivotTable . '.' . $pivotTypeColumn, $typeValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    foreach ($orderBy as $spec) {
                        $statement->orderBy($spec['column'], $spec['direction']);
                    }

                    if ($limit !== null) {
                        $statement->limit($limit, $offset);
                    }
                },
            ),
            countBuilder: function (array $criteria) use ($manager, $pivotTable, $pivotSourceColumns, $pivotTargetColumns, $pivotTypeColumn, $parentPkColumns, $targetPkColumns, $targetTable, $typeValue, $parentValues): int {
                $statement = $manager->connection->count($targetTable);

                $this->applyBelongsToManyPivotJoin(
                    statement: $statement,
                    pivotTable: $pivotTable,
                    pivotTargetColumns: $pivotTargetColumns,
                    targetTable: $targetTable,
                    targetPkColumns: $targetPkColumns,
                    pivotSourceColumns: $pivotSourceColumns,
                    parentPkColumns: $parentPkColumns,
                    parentValues: $parentValues,
                );

                $statement->where($pivotTable . '.' . $pivotTypeColumn, $typeValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
            initialCriteriaStack: $shaped->criteriaStack ?? [],
            initialOrderBy: $shaped->orderBy ?? [],
            initialLimit: $shaped?->limit,
            initialOffset: $shaped?->offset,
            manager: $manager,
            modelClass: $relatedClass,
        );
    }

    private function assignSingleRowEager(
        object $parent,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
        mixed $sourceValue,
        ?object $target,
    ): void {
        $reflector = PropertyReflector::createFromObject($parent, $relation->property);

        if ($sourceValue === null) {
            if (!$relation->nullable) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromMissingForeignKeyValue(
                    modelClass: $metaData->model,
                    property: $relation->property,
                );
                // @codeCoverageIgnoreEnd
            }

            $reflector->setValue($parent, null);

            return;
        }

        if ($target === null) {
            if ($relation->nullable) {
                $reflector->setValue($parent, null);

                return;
            }

            throw ModelException::fromMissingRelatedRecord(
                modelClass: $metaData->model,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        $reflector->setValue($parent, $target);
    }

    private function resolveSourceProperty(
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): string {
        /** @var HasOneThrough|HasManyThrough $attribute */
        $attribute = $relation->attribute;

        if ($attribute->localKey !== null) {
            return $this->findPropertyByColumn($metaData, $attribute->localKey, $relation->property);
        }

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        return $metaData->key->property;
    }

    /**
     * @return non-empty-list<string>
     */
    private function resolveSourceColumns(
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): array {
        $attribute = $relation->attribute;

        if ($attribute instanceof BelongsTo) {
            if ($relation->foreignKeyColumns !== null) {
                return $relation->foreignKeyColumns;
            }
        }

        if ($attribute instanceof HasOne || $attribute instanceof HasMany) {
            if ($relation->referencedKeyColumns !== null) {
                return $relation->referencedKeyColumns;
            }
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $relation->property,
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return non-empty-list<string>
     */
    private function resolveTargetColumns(
        ModelRelationInterface $relation,
    ): array {
        $attribute = $relation->attribute;

        if ($attribute instanceof HasOne || $attribute instanceof HasMany) {
            if ($relation->foreignKeyColumns !== null) {
                return $relation->foreignKeyColumns;
            }
        }

        if ($attribute instanceof BelongsTo) {
            if ($relation->referencedKeyColumns !== null) {
                return $relation->referencedKeyColumns;
            }
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $relation->relatedClass,
            property: $relation->property,
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return non-empty-list<string>
     */
    private function resolvePkColumns(
        ModelMetaDataInterface $metaData,
    ): array {
        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            return [
                $metaData->key->column,
            ];
        }

        if ($metaData->key instanceof ModelCompositeKeyInterface) {
            return \array_values($metaData->key->columns);
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromCantFetchWithoutPrimaryKey(
            modelClass: $metaData->model,
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param non-empty-list<string> $pivotTargetColumns
     * @param non-empty-list<string> $targetPkColumns
     * @param non-empty-list<string> $pivotSourceColumns
     * @param non-empty-list<string> $parentPkColumns
     * @param non-empty-array<string, int|string> $parentValues
     */
    private function applyBelongsToManyPivotJoin(
        WhereStatementInterface $statement,
        string $pivotTable,
        array $pivotTargetColumns,
        string $targetTable,
        array $targetPkColumns,
        array $pivotSourceColumns,
        array $parentPkColumns,
        array $parentValues,
    ): void {
        $statement->innerJoin(
            table: $pivotTable,
            first: $pivotTable . '.' . $pivotTargetColumns[0],
            second: $targetTable . '.' . $targetPkColumns[0],
        );

        $arity = \sizeof($pivotTargetColumns);

        for ($index = 1; $index < $arity; $index++) {
            $statement->whereColumn(
                column: $pivotTable . '.' . $pivotTargetColumns[$index],
                other: $targetTable . '.' . $targetPkColumns[$index],
            );
        }

        foreach ($pivotSourceColumns as $index => $pivotSourceColumn) {
            $parentColumn = $parentPkColumns[$index];
            $statement->where($pivotTable . '.' . $pivotSourceColumn, $parentValues[$parentColumn]);
        }
    }

    /**
     * @param non-empty-list<string> $sourceColumns
     * @param array<string, string> $sourcePropertyNames
     * @return non-empty-array<string, int|string>|null
     */
    private function readTupleFromModel(
        object $model,
        ModelMetaDataInterface $metaData,
        array $sourceColumns,
        array $sourcePropertyNames,
    ): ?array {
        $tuple = [];

        foreach ($sourceColumns as $sourceColumn) {
            $propertyName = $sourcePropertyNames[$sourceColumn];
            $value = PropertyReflector::createFromObject($model, $propertyName)->getValue($model);

            if ($value === null) {
                return null;
            }

            if (!\is_int($value) && !\is_string($value)) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $propertyName,
                    actualType: \get_debug_type($value),
                );
                // @codeCoverageIgnoreEnd
            }

            $tuple[$sourceColumn] = $value;
        }

        return $tuple;
    }

    /**
     * @param array<string, string> $targetPropertyNames
     * @param non-empty-list<string> $sourceColumns
     * @param non-empty-list<string> $targetColumns
     * @return non-empty-array<string, int|string>|null
     */
    private function readTupleFromModelByColumnMap(
        object $model,
        array $targetPropertyNames,
        array $sourceColumns,
        array $targetColumns,
    ): ?array {
        $tuple = [];

        foreach ($targetColumns as $index => $targetColumn) {
            $sourceColumn = $sourceColumns[$index];
            $propertyName = $targetPropertyNames[$targetColumn];
            $value = PropertyReflector::createFromObject($model, $propertyName)->getValue($model);

            if (!\is_int($value) && !\is_string($value)) {
                return null; // @codeCoverageIgnore
            }

            $tuple[$sourceColumn] = $value;
        }

        return $tuple;
    }

    /**
     * @param non-empty-list<string> $sourceColumns
     * @param non-empty-list<string> $targetColumns
     * @param non-empty-array<string, non-empty-array<string, int|string>> $tuplesByHash
     */
    private function applyCompositeTuplesFilter(
        WhereStatementInterface $statement,
        array $sourceColumns,
        array $targetColumns,
        array $tuplesByHash,
    ): void {
        if (\sizeof($sourceColumns) === 1) {
            $sourceColumn = $sourceColumns[0];
            $targetColumn = $targetColumns[0];
            $values = [];

            foreach ($tuplesByHash as $tuple) {
                $values[] = $tuple[$sourceColumn];
            }

            $statement->whereIn($targetColumn, $values);

            return;
        }

        $isFirst = true;

        foreach ($tuplesByHash as $tuple) {
            $groupCallback = static function (WhereStatementInterface $inner) use ($sourceColumns, $targetColumns, $tuple): void {
                foreach ($sourceColumns as $index => $sourceColumn) {
                    $targetColumn = $targetColumns[$index];
                    $inner->where($targetColumn, $tuple[$sourceColumn]);
                }
            };

            if ($isFirst) {
                $statement->whereGroup($groupCallback);
                $isFirst = false;
            } else {
                $statement->orWhereGroup($groupCallback);
            }
        }
    }

    private function findPropertyByColumn(
        ModelMetaDataInterface $metaData,
        string $column,
        string $relationProperty,
    ): string {
        foreach ($metaData->columns as $modelColumn) {
            if ($modelColumn->column === $column) {
                return $modelColumn->property;
            }
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromRelationKeyReferencesUnknownColumn(
            modelClass: $metaData->model,
            property: $relationProperty,
            keyKind: 'column',
            keyValue: $column,
            referencedClass: $metaData->model,
        );
        // @codeCoverageIgnoreEnd
    }
}
