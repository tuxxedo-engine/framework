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
use Tuxxedo\Model\MetaData\MetaDataInterface;
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

        $this->attachRelations($model, $metaData);
        $this->modelsManager->dirtyTracker->recordSnapshot($model, $metaData);

        return $model;
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
            fn (): object => $this->loadSingleRelation($metaData, $relation, $sourceValue),
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $proxy);
    }

    private function setupHasManyRelation(
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

        $targetColumn = $this->resolveTargetColumn($relation);
        $manager = $this->modelsManager;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($targetColumn, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement->where($targetColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $targetColumn, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($targetColumn, $sourceValue);

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
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourceProperty = PropertyReflector::createFromObject($model, $metaData->key->property);
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

        /** @var BelongsToMany $attribute */
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

        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $pivotTable = $attribute->table;
        $pivotLocalKey = $attribute->localKey;
        $pivotForeignKey = $attribute->foreignKey;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                    ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

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
        $idColumnProperty = $this->findPropertyByColumn(
            metaData: $metaData,
            column: $morphTo->idColumn,
            relationProperty: $morphTo->property,
        );

        $typeValue = PropertyReflector::createFromObject($model, $typeColumnProperty)->getValue($model);
        $idValue = PropertyReflector::createFromObject($model, $idColumnProperty)->getValue($model);
        $morphProperty = PropertyReflector::createFromObject($model, $morphTo->property);

        if ($typeValue === null || $typeValue === '' || $idValue === null) {
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

        if (!\is_scalar($idValue)) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $idColumnProperty,
                actualType: \get_debug_type($idValue),
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

        $proxy = $reflectionClass->newLazyProxy(
            function () use ($manager, $targetClass, $idValue, $sourceMetaData, $morphPropertyName): object {
                $targetMetaData = $manager->metaData->getModel($targetClass);

                if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromCantFetchWithoutPrimaryKey(
                        modelClass: $targetClass,
                    );
                    // @codeCoverageIgnoreEnd
                }

                $targetPrimaryColumn = $targetMetaData->key->column;
                $result = $manager->findFirst(
                    $targetClass,
                    static function (WhereStatementInterface $statement) use ($targetPrimaryColumn, $idValue): void {
                        $statement->where($targetPrimaryColumn, $idValue);
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
        $sourceProperty = PropertyReflector::createFromObject($model, $this->resolveMorphSourceProperty($metaData, $attribute->localKey, $relation->property));
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

        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $reflectionClass = new \ReflectionClass($relation->relatedClass);
        $manager = $this->modelsManager;
        $relatedClass = $relation->relatedClass;
        $typeColumn = $attribute->typeColumn;
        $idColumn = $attribute->idColumn;
        $sourceMetaData = $metaData;
        $relationProperty = $relation->property;

        $proxy = $reflectionClass->newLazyProxy(
            function () use ($manager, $relatedClass, $typeColumn, $idColumn, $typeValue, $sourceValue, $sourceMetaData, $relationProperty): object {
                $result = $manager->findFirst(
                    $relatedClass,
                    static function (WhereStatementInterface $statement) use ($typeColumn, $idColumn, $typeValue, $sourceValue): void {
                        $statement
                            ->where($typeColumn, $typeValue)
                            ->where($idColumn, $sourceValue);
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
        $sourceProperty = PropertyReflector::createFromObject($model, $this->resolveMorphSourceProperty($metaData, $attribute->localKey, $relation->property));
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

        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $manager = $this->modelsManager;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;
        $typeColumn = $attribute->typeColumn;
        $idColumn = $attribute->idColumn;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($typeColumn, $idColumn, $typeValue, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->where($typeColumn, $typeValue)
                        ->where($idColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $typeColumn, $idColumn, $typeValue, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($typeColumn, $typeValue)
                    ->where($idColumn, $sourceValue);

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
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourceProperty = PropertyReflector::createFromObject($model, $metaData->key->property);
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

        /** @var MorphToMany $attribute */
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

        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $pivotTable = $attribute->table;
        $pivotTypeColumn = $attribute->typeColumn;
        $pivotIdColumn = $attribute->idColumn;
        $pivotForeignKey = $attribute->foreignKey;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotTypeColumn, $pivotIdColumn, $targetTable, $targetPrimaryKey, $typeValue, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotTypeColumn, $typeValue)
                        ->where($pivotTable . '.' . $pivotIdColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $pivotTable, $pivotForeignKey, $pivotTypeColumn, $pivotIdColumn, $targetTable, $targetPrimaryKey, $typeValue, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                    ->where($pivotTable . '.' . $pivotTypeColumn, $typeValue)
                    ->where($pivotTable . '.' . $pivotIdColumn, $sourceValue);

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

    private function resolveMorphSourceProperty(
        ModelMetaDataInterface $metaData,
        ?string $localKey,
        string $relationProperty,
    ): string {
        if ($localKey === null) {
            if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $metaData->model,
                );
                // @codeCoverageIgnoreEnd
            }

            return $metaData->key->property;
        }

        return $this->findPropertyByColumn($metaData, $localKey, $relationProperty);
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

    private function loadSingleRelation(
        ModelMetaDataInterface $sourceMetaData,
        ModelRelationInterface $relation,
        string|int|float|bool $sourceValue,
    ): object {
        $targetColumn = $this->resolveTargetColumn($relation);

        $result = $this->modelsManager->findFirst(
            $relation->relatedClass,
            static function (WhereStatementInterface $statement) use ($targetColumn, $sourceValue): void {
                $statement->where($targetColumn, $sourceValue);
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
        $idColumnProperty = $this->findPropertyByColumn(
            metaData: $metaData,
            column: $morphTo->idColumn,
            relationProperty: $morphTo->property,
        );

        /** @var array<string, array<int|string, int|string>> $idsByType */
        $idsByType = [];

        foreach ($parents as $parent) {
            $typeValue = PropertyReflector::createFromObject($parent, $typeColumnProperty)->getValue($parent);
            $idValue = PropertyReflector::createFromObject($parent, $idColumnProperty)->getValue($parent);

            if ($typeValue === null || $typeValue === '' || $idValue === null) {
                continue;
            }

            if (!\is_string($typeValue)) {
                continue; // @codeCoverageIgnore
            }

            if (!\is_int($idValue) && !\is_string($idValue)) {
                continue; // @codeCoverageIgnore
            }

            $idsByType[$typeValue][$idValue] = $idValue;
        }

        /** @var array<string, array<int|string, object>> $targetsByTypeAndId */
        $targetsByTypeAndId = [];
        $manager = $this->modelsManager;

        foreach ($idsByType as $typeValue => $ids) {
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

            if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $targetClass,
                );
                // @codeCoverageIgnoreEnd
            }

            $targetPrimaryColumn = $targetMetaData->key->column;
            $targetPrimaryProperty = $targetMetaData->key->property;
            $idValues = \array_values($ids);

            if (\sizeof($idValues) === 0) {
                continue; // @codeCoverageIgnore
            }

            $targetQuery = $manager->connection->select($targetMetaData->table)
                ->whereIn($targetPrimaryColumn, $idValues);

            $shaped = $this->shapeConstraint($constraint, $targetClass);
            $this->applyConstraintToBatch($targetQuery, $shaped);

            $rows = $targetQuery->fetchAll($targetClass, $manager->hydrator);

            foreach ($rows as $row) {
                $pkValue = PropertyReflector::createFromObject($row, $targetPrimaryProperty)->getValue($row);

                if (!\is_int($pkValue) && !\is_string($pkValue)) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByTypeAndId[$typeValue][$pkValue] = $row;
            }
        }

        foreach ($parents as $parent) {
            $typeValue = PropertyReflector::createFromObject($parent, $typeColumnProperty)->getValue($parent);
            $idValue = PropertyReflector::createFromObject($parent, $idColumnProperty)->getValue($parent);
            $morphProperty = PropertyReflector::createFromObject($parent, $morphTo->property);

            if ($typeValue === null || $typeValue === '' || $idValue === null) {
                if (!$morphTo->nullable) {
                    throw ModelException::fromMissingForeignKeyValue(
                        modelClass: $metaData->model,
                        property: $morphTo->property,
                    );
                }

                $morphProperty->setValue($parent, null);

                continue;
            }

            if (!\is_string($typeValue) || (!\is_int($idValue) && !\is_string($idValue))) {
                continue; // @codeCoverageIgnore
            }

            $target = $targetsByTypeAndId[$typeValue][$idValue] ?? null;

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
        $sourcePropertyName = $this->resolveSourceProperty($metaData, $relation);
        $targetColumn = $this->resolveTargetColumn($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $targetForeignProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $targetColumn,
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

        /** @var array<int|string, list<object>> $grouped */
        $grouped = [];

        if (\sizeof($sourceValues) > 0) {
            $batchQuery = $manager->connection->select($targetTable)
                ->whereIn($targetColumn, \array_values($sourceValues));

            $this->applyConstraintToBatch($batchQuery, $shaped);

            $batchRows = $batchQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($batchRows as $row) {
                $fkValue = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                if (!\is_int($fkValue) && !\is_string($fkValue)) {
                    continue; // @codeCoverageIgnore
                }

                $grouped[$fkValue][] = $row;
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $prefetched = \is_int($sourceValue) || \is_string($sourceValue)
                ? ($grouped[$sourceValue] ?? [])
                : [];
            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildHasManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetColumn: $targetColumn,
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
    private function buildHasManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $targetColumn,
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
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($targetColumn, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement->where($targetColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $targetColumn, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($targetColumn, $sourceValue);

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

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyName = $metaData->key->property;
        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $targetPrimaryProperty = $targetMetaData->key->property;
        $pivotTable = $attribute->table;
        $pivotLocalKey = $attribute->localKey;
        $pivotForeignKey = $attribute->foreignKey;

        /** @var array<int|string, int|string> $sourceValues */
        $sourceValues = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);

            if ($value === null) {
                continue; // @codeCoverageIgnore
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

        /** @var array<int|string, list<int|string>> $pivotPairs */
        $pivotPairs = [];
        /** @var array<int|string, object> $targetsByPk */
        $targetsByPk = [];

        if (\sizeof($sourceValues) > 0) {
            $pivotResult = $manager->connection
                ->select($pivotTable)
                ->select($pivotLocalKey, $pivotForeignKey)
                ->whereIn($pivotLocalKey, \array_values($sourceValues))
                ->execute();

            /** @var array<int|string, int|string> $foreignKeys */
            $foreignKeys = [];

            foreach ($pivotResult as $row) {
                $local = $row->properties[$pivotLocalKey] ?? null;
                $foreign = $row->properties[$pivotForeignKey] ?? null;

                if (!\is_int($local) && !\is_string($local)) {
                    continue; // @codeCoverageIgnore
                }

                if (!\is_int($foreign) && !\is_string($foreign)) {
                    continue; // @codeCoverageIgnore
                }

                $pivotPairs[$local][] = $foreign;
                $foreignKeys[$foreign] = $foreign;
            }

            if (\sizeof($foreignKeys) > 0) {
                $targetQuery = $manager->connection
                    ->select($targetTable)
                    ->whereIn($targetPrimaryKey, \array_values($foreignKeys));

                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $pkValue = PropertyReflector::createFromObject($row, $targetPrimaryProperty)->getValue($row);

                    if (!\is_int($pkValue) && !\is_string($pkValue)) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByPk[$pkValue] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            /** @var list<object> $prefetched */
            $prefetched = [];

            if (\is_int($sourceValue) || \is_string($sourceValue)) {
                foreach ($pivotPairs[$sourceValue] ?? [] as $fk) {
                    if (isset($targetsByPk[$fk])) {
                        $prefetched[] = $targetsByPk[$fk];
                    }
                }
            }

            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildBelongsToManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetPrimaryKey: $targetPrimaryKey,
                pivotTable: $pivotTable,
                pivotLocalKey: $pivotLocalKey,
                pivotForeignKey: $pivotForeignKey,
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
    private function buildBelongsToManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $targetPrimaryKey,
        string $pivotTable,
        string $pivotLocalKey,
        string $pivotForeignKey,
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
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                    ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

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
        $sourcePropertyName = $this->resolveSourceProperty($metaData, $relation);
        $targetColumn = $this->resolveTargetColumn($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $targetForeignProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $targetColumn,
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

        /** @var array<int|string, object> $targetsByFk */
        $targetsByFk = [];

        if (\sizeof($sourceValues) > 0) {
            $targetQuery = $manager->connection
                ->select($targetTable)
                ->whereIn($targetColumn, \array_values($sourceValues));

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

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $sourceValue,
                target: \is_int($sourceValue) || \is_string($sourceValue)
                    ? ($targetsByFk[$sourceValue] ?? null)
                    : null,
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
        $sourcePropertyName = $this->resolveSourceProperty($metaData, $relation);
        $targetColumn = $this->resolveTargetColumn($relation);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;

        $targetForeignProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $targetColumn,
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

        /** @var array<int|string, object> $targetsByOwnerKey */
        $targetsByOwnerKey = [];

        if (\sizeof($sourceValues) > 0) {
            $targetQuery = $manager->connection
                ->select($targetTable)
                ->whereIn($targetColumn, \array_values($sourceValues));

            $this->applyConstraintToBatch($targetQuery, $shaped);

            $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($targetRows as $row) {
                $ownerValue = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                if (!\is_int($ownerValue) && !\is_string($ownerValue)) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByOwnerKey[$ownerValue] = $row;
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $sourceValue,
                target: \is_int($sourceValue) || \is_string($sourceValue)
                    ? ($targetsByOwnerKey[$sourceValue] ?? null)
                    : null,
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
        $sourcePropertyName = $this->resolveMorphSourceProperty($metaData, $attribute->localKey, $relation->property);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;
        $typeColumn = $attribute->typeColumn;
        $idColumn = $attribute->idColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $targetIdProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $idColumn,
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

        /** @var array<int|string, object> $targetsByFk */
        $targetsByFk = [];

        if (\sizeof($sourceValues) > 0) {
            $targetQuery = $manager->connection
                ->select($targetTable)
                ->where($typeColumn, $typeValue)
                ->whereIn($idColumn, \array_values($sourceValues));

            $this->applyConstraintToBatch($targetQuery, $shaped);

            $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($targetRows as $row) {
                $fk = PropertyReflector::createFromObject($row, $targetIdProperty)->getValue($row);

                if (!\is_int($fk) && !\is_string($fk)) {
                    continue; // @codeCoverageIgnore
                }

                $targetsByFk[$fk] = $row;
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $this->assignSingleRowEager(
                parent: $parent,
                metaData: $metaData,
                relation: $relation,
                sourceValue: $sourceValue,
                target: \is_int($sourceValue) || \is_string($sourceValue)
                    ? ($targetsByFk[$sourceValue] ?? null)
                    : null,
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
        $sourcePropertyName = $this->resolveMorphSourceProperty($metaData, $attribute->localKey, $relation->property);

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);
        $targetTable = $targetMetaData->table;
        $typeColumn = $attribute->typeColumn;
        $idColumn = $attribute->idColumn;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );
        $targetIdProperty = $this->findPropertyByColumn(
            metaData: $targetMetaData,
            column: $idColumn,
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

        /** @var array<int|string, list<object>> $grouped */
        $grouped = [];

        if (\sizeof($sourceValues) > 0) {
            $batchQuery = $manager->connection->select($targetTable)
                ->where($typeColumn, $typeValue)
                ->whereIn($idColumn, \array_values($sourceValues));

            $this->applyConstraintToBatch($batchQuery, $shaped);

            $batchRows = $batchQuery->fetchAll($relatedClass, $manager->hydrator);

            foreach ($batchRows as $row) {
                $fkValue = PropertyReflector::createFromObject($row, $targetIdProperty)->getValue($row);

                if (!\is_int($fkValue) && !\is_string($fkValue)) {
                    continue; // @codeCoverageIgnore
                }

                $grouped[$fkValue][] = $row;
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $prefetched = \is_int($sourceValue) || \is_string($sourceValue)
                ? ($grouped[$sourceValue] ?? [])
                : [];
            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildMorphManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                typeColumn: $typeColumn,
                idColumn: $idColumn,
                typeValue: $typeValue,
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
    private function buildMorphManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $typeColumn,
        string $idColumn,
        string $typeValue,
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
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($typeColumn, $idColumn, $typeValue, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->where($typeColumn, $typeValue)
                        ->where($idColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $typeColumn, $idColumn, $typeValue, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->where($typeColumn, $typeValue)
                    ->where($idColumn, $sourceValue);

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

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
            // @codeCoverageIgnoreEnd
        }

        $sourcePropertyName = $metaData->key->property;
        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $targetPrimaryProperty = $targetMetaData->key->property;
        $pivotTable = $attribute->table;
        $pivotTypeColumn = $attribute->typeColumn;
        $pivotIdColumn = $attribute->idColumn;
        $pivotForeignKey = $attribute->foreignKey;
        $typeValue = MorphTypeResolver::encode(
            class: $metaData->model,
            typeMap: $attribute->typeMap,
        );

        /** @var array<int|string, int|string> $sourceValues */
        $sourceValues = [];

        foreach ($parents as $parent) {
            $value = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);

            if ($value === null) {
                continue; // @codeCoverageIgnore
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

        /** @var array<int|string, list<int|string>> $pivotPairs */
        $pivotPairs = [];
        /** @var array<int|string, object> $targetsByPk */
        $targetsByPk = [];

        if (\sizeof($sourceValues) > 0) {
            $pivotResult = $manager->connection
                ->select($pivotTable)
                ->select($pivotIdColumn, $pivotForeignKey)
                ->where($pivotTypeColumn, $typeValue)
                ->whereIn($pivotIdColumn, \array_values($sourceValues))
                ->execute();

            /** @var array<int|string, int|string> $foreignKeys */
            $foreignKeys = [];

            foreach ($pivotResult as $row) {
                $local = $row->properties[$pivotIdColumn] ?? null;
                $foreign = $row->properties[$pivotForeignKey] ?? null;

                if (!\is_int($local) && !\is_string($local)) {
                    continue; // @codeCoverageIgnore
                }

                if (!\is_int($foreign) && !\is_string($foreign)) {
                    continue; // @codeCoverageIgnore
                }

                $pivotPairs[$local][] = $foreign;
                $foreignKeys[$foreign] = $foreign;
            }

            if (\sizeof($foreignKeys) > 0) {
                $targetQuery = $manager->connection
                    ->select($targetTable)
                    ->whereIn($targetPrimaryKey, \array_values($foreignKeys));

                $this->applyConstraintToBatch($targetQuery, $shaped);

                $targetRows = $targetQuery->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $pkValue = PropertyReflector::createFromObject($row, $targetPrimaryProperty)->getValue($row);

                    if (!\is_int($pkValue) && !\is_string($pkValue)) {
                        continue; // @codeCoverageIgnore
                    }

                    $targetsByPk[$pkValue] = $row;
                }
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            /** @var list<object> $prefetched */
            $prefetched = [];

            if (\is_int($sourceValue) || \is_string($sourceValue)) {
                foreach ($pivotPairs[$sourceValue] ?? [] as $fk) {
                    if (isset($targetsByPk[$fk])) {
                        $prefetched[] = $targetsByPk[$fk];
                    }
                }
            }

            $prefetched = $this->sliceForConstraint($prefetched, $shaped);

            $relationInstance = $this->buildMorphToManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetPrimaryKey: $targetPrimaryKey,
                pivotTable: $pivotTable,
                pivotTypeColumn: $pivotTypeColumn,
                pivotIdColumn: $pivotIdColumn,
                pivotForeignKey: $pivotForeignKey,
                typeValue: $typeValue,
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
    private function buildMorphToManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $targetPrimaryKey,
        string $pivotTable,
        string $pivotTypeColumn,
        string $pivotIdColumn,
        string $pivotForeignKey,
        string $typeValue,
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
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotTypeColumn, $pivotIdColumn, $targetTable, $targetPrimaryKey, $typeValue, $sourceValue, $criteria, $orderBy, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotTypeColumn, $typeValue)
                        ->where($pivotTable . '.' . $pivotIdColumn, $sourceValue);

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
            countBuilder: static function (array $criteria) use ($manager, $pivotTable, $pivotForeignKey, $pivotTypeColumn, $pivotIdColumn, $targetTable, $targetPrimaryKey, $typeValue, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                    ->where($pivotTable . '.' . $pivotTypeColumn, $typeValue)
                    ->where($pivotTable . '.' . $pivotIdColumn, $sourceValue);

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
        $attribute = $relation->attribute;

        if ($attribute instanceof BelongsTo) {
            return $this->findPropertyByColumn($metaData, $attribute->foreignKey, $relation->property);
        }

        if (
            $attribute instanceof HasOne ||
            $attribute instanceof HasMany ||
            $attribute instanceof HasOneThrough ||
            $attribute instanceof HasManyThrough
        ) {
            $localKey = $attribute->localKey;

            if ($localKey === null) {
                if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
                    // @codeCoverageIgnoreStart
                    throw ModelException::fromCantFetchWithoutPrimaryKey(
                        modelClass: $metaData->model,
                    );
                    // @codeCoverageIgnoreEnd
                }

                return $metaData->key->property;
            }

            return $this->findPropertyByColumn($metaData, $localKey, $relation->property);
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $relation->property,
        );
        // @codeCoverageIgnoreEnd
    }

    private function resolveTargetColumn(
        ModelRelationInterface $relation,
    ): string {
        $attribute = $relation->attribute;

        if ($attribute instanceof HasOne || $attribute instanceof HasMany) {
            return $attribute->foreignKey;
        }

        if ($attribute instanceof BelongsTo) {
            $ownerKey = $attribute->ownerKey;

            if ($ownerKey !== null) {
                return $ownerKey;
            }

            $targetMetaData = $this->modelsManager->metaData->getModel($relation->relatedClass);

            if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
                // @codeCoverageIgnoreStart
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $relation->relatedClass,
                );
                // @codeCoverageIgnoreEnd
            }

            return $targetMetaData->key->column;
        }

        // @codeCoverageIgnoreStart
        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $relation->relatedClass,
            property: $relation->property,
        );
        // @codeCoverageIgnoreEnd
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
