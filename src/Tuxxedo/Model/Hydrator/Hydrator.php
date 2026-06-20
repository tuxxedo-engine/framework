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
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\ModelsManagerInterface;
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
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
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

        if ($sourceValue === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched([]),
            );

            return;
        }

        if (!\is_scalar($sourceValue)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
        }

        $targetColumn = $this->resolveTargetColumn($relation);
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (WhereStatementInterface $statement) use ($targetColumn, $sourceValue, $criteria, $limit, $offset): void {
                    $statement->where($targetColumn, $sourceValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    if ($limit !== null && $statement instanceof SelectStatementInterface) {
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
        );

        PropertyReflector::createFromObject($model, $relation->property)->setValue($model, $relationInstance);
    }

    private function setupBelongsToManyRelation(
        object $model,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        $sourceProperty = PropertyReflector::createFromObject($model, $metaData->key->property);
        $sourceValue = $sourceProperty->getValue($model);

        if ($sourceValue === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched([]),
            );

            return;
        }

        if (!\is_scalar($sourceValue)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
        }

        /** @var BelongsToMany $attribute */
        $attribute = $relation->attribute;
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
        }

        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $pivotTable = $attribute->table;
        $pivotLocalKey = $attribute->localKey;
        $pivotForeignKey = $attribute->foreignKey;

        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (WhereStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue, $criteria, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    if ($limit !== null && $statement instanceof SelectStatementInterface) {
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
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
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

        if ($sourceValue === null) {
            PropertyReflector::createFromObject($model, $relation->property)->setValue(
                $model,
                Relation::createFromPrefetched([]),
            );

            return;
        }

        if (!\is_scalar($sourceValue)) {
            throw ModelException::fromPropertyValueMustBeScalar(
                modelClass: $metaData->model,
                property: $sourceProperty->name,
                actualType: \get_debug_type($sourceValue),
            );
        }

        /** @var HasManyThrough $attribute */
        $attribute = $relation->attribute;
        $manager = $this->modelsManager;
        $relatedClass = $relation->relatedClass;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
        }

        $throughTable = $manager->metaData->getModel($attribute->through)->table;
        $throughSecondLocalKey = $this->resolveThroughSecondLocalKeyColumn($attribute);
        $targetTable = $targetMetaData->table;
        $targetPrimaryKey = $targetMetaData->key->column;
        $secondKey = $attribute->secondKey;
        $firstKey = $attribute->firstKey;

        // @todo HasManyThrough loader bypasses ModelsManager::findAll() because distinct() lives on SelectStatement and the criteria closure is narrowed to WhereStatementInterface. Soft-delete filtering on the Through-far model is therefore not applied here — revisit once findAll exposes a shape-modifier hook or once the soft-delete filter moves into the Statement layer.
        $relationInstance = Relation::createFromBuilder(
            loaderBuilder: static function (array $criteria, ?int $limit, ?int $offset) use ($manager, $targetTable, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue, $relatedClass): iterable {
                $statement = $manager->connection->select($targetTable)
                    ->distinct()
                    ->innerJoin($throughTable, $throughTable . '.' . $throughSecondLocalKey, $targetTable . '.' . $secondKey)
                    ->where($throughTable . '.' . $firstKey, $sourceValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                if ($limit !== null) {
                    $statement->limit($limit, $offset);
                }

                return $statement->fetchAll($relatedClass, $manager->hydrator);
            },
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $targetPrimaryKey, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->column($targetTable . '.' . $targetPrimaryKey)
                    ->distinct()
                    ->innerJoin($throughTable, $throughTable . '.' . $throughSecondLocalKey, $targetTable . '.' . $secondKey)
                    ->where($throughTable . '.' . $firstKey, $sourceValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
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
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $attribute->through,
            );
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
     * @param array<string, ?\Closure(Relation<object>): Relation<object>> $with
     */
    public function eagerLoad(
        array $parents,
        array $with,
    ): void {
        if (\sizeof($parents) === 0 || \sizeof($with) === 0) {
            return;
        }

        $firstParent = $parents[\array_key_first($parents)];
        $metaData = $this->modelsManager->metaData->getModel($firstParent::class);

        foreach ($with as $relationName => $constraint) {
            if ($constraint !== null) {
                throw ModelException::fromEagerLoadingConstraintsNotYetSupported(
                    relationName: $relationName,
                );
            }

            $relation = $this->findRelationByName($metaData, $relationName);
            $attribute = $relation->attribute;

            if ($attribute instanceof HasMany) {
                $this->eagerLoadHasMany($parents, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof BelongsToMany) {
                $this->eagerLoadBelongsToMany($parents, $metaData, $relation);

                continue;
            }

            if ($attribute instanceof HasManyThrough) {
                $this->eagerLoadHasManyThrough($parents, $metaData, $relation);

                continue;
            }

            throw ModelException::fromEagerLoadingNotYetSupported(
                attributeClass: $attribute::class,
            );
        }
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
     */
    private function eagerLoadHasMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
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
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
            }

            $sourceValues[$value] = $value;
        }

        /** @var array<int|string, list<object>> $grouped */
        $grouped = [];

        if (\sizeof($sourceValues) > 0) {
            $batchRows = $manager->connection->select($targetTable)
                ->whereIn($targetColumn, \array_values($sourceValues))
                ->fetchAll($relatedClass, $manager->hydrator);

            foreach ($batchRows as $row) {
                $fkValue = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                if (!\is_int($fkValue) && !\is_string($fkValue)) {
                    continue;
                }

                $grouped[$fkValue][] = $row;
            }
        }

        foreach ($parents as $parent) {
            $sourceValue = PropertyReflector::createFromObject($parent, $sourcePropertyName)->getValue($parent);
            $prefetched = \is_int($sourceValue) || \is_string($sourceValue)
                ? ($grouped[$sourceValue] ?? [])
                : [];

            $relationInstance = $this->buildHasManyEagerRelation(
                manager: $manager,
                relatedClass: $relatedClass,
                targetTable: $targetTable,
                targetColumn: $targetColumn,
                sourceValue: $sourceValue,
                prefetched: $prefetched,
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param list<object> $prefetched
     * @return Relation<object>
     */
    private function buildHasManyEagerRelation(
        ModelsManagerInterface $manager,
        string $relatedClass,
        string $targetTable,
        string $targetColumn,
        mixed $sourceValue,
        array $prefetched,
    ): Relation {
        if (!\is_int($sourceValue) && !\is_string($sourceValue)) {
            return Relation::createFromPrefetched(
                values: $prefetched,
            );
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static fn (array $criteria, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (WhereStatementInterface $statement) use ($targetColumn, $sourceValue, $criteria, $limit, $offset): void {
                    $statement->where($targetColumn, $sourceValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    if ($limit !== null && $statement instanceof SelectStatementInterface) {
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
        );
    }

    /**
     * @param object[] $parents
     */
    private function eagerLoadBelongsToMany(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
    ): void {
        /** @var BelongsToMany $attribute */
        $attribute = $relation->attribute;

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        /** @var class-string $relatedClass */
        $relatedClass = $relation->relatedClass;
        $manager = $this->modelsManager;
        $targetMetaData = $manager->metaData->getModel($relatedClass);

        if (!$targetMetaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
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
                continue;
            }

            if (!\is_int($value) && !\is_string($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
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
                    continue;
                }

                if (!\is_int($foreign) && !\is_string($foreign)) {
                    continue;
                }

                $pivotPairs[$local][] = $foreign;
                $foreignKeys[$foreign] = $foreign;
            }

            if (\sizeof($foreignKeys) > 0) {
                $targetRows = $manager->connection
                    ->select($targetTable)
                    ->whereIn($targetPrimaryKey, \array_values($foreignKeys))
                    ->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $pkValue = PropertyReflector::createFromObject($row, $targetPrimaryProperty)->getValue($row);

                    if (!\is_int($pkValue) && !\is_string($pkValue)) {
                        continue;
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
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param list<object> $prefetched
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
    ): Relation {
        if (!\is_int($sourceValue) && !\is_string($sourceValue)) {
            return Relation::createFromPrefetched(
                values: $prefetched,
            );
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static fn (array $criteria, ?int $limit, ?int $offset): iterable => $manager->findAll(
                $relatedClass,
                static function (WhereStatementInterface $statement) use ($pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue, $criteria, $limit, $offset): void {
                    $statement
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);

                    foreach ($criteria as $extra) {
                        $extra($statement);
                    }

                    if ($limit !== null && $statement instanceof SelectStatementInterface) {
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
        );
    }

    /**
     * @param object[] $parents
     */
    private function eagerLoadHasManyThrough(
        array $parents,
        ModelMetaDataInterface $metaData,
        ModelRelationInterface $relation,
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
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $relatedClass,
            );
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
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $sourcePropertyName,
                    actualType: \get_debug_type($value),
                );
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
                    continue;
                }

                if (!\is_int($second) && !\is_string($second)) {
                    continue;
                }

                $throughPairs[$first][] = $second;
                $targetForeignValues[$second] = $second;
            }

            if (\sizeof($targetForeignValues) > 0) {
                $targetRows = $manager->connection
                    ->select($targetTable)
                    ->whereIn($secondKey, \array_values($targetForeignValues))
                    ->fetchAll($relatedClass, $manager->hydrator);

                foreach ($targetRows as $row) {
                    $fk = PropertyReflector::createFromObject($row, $targetForeignProperty)->getValue($row);

                    if (!\is_int($fk) && !\is_string($fk)) {
                        continue;
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
            );

            PropertyReflector::createFromObject($parent, $relation->property)->setValue($parent, $relationInstance);
        }
    }

    /**
     * @param class-string $relatedClass
     * @param list<object> $prefetched
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
    ): Relation {
        if (!\is_int($sourceValue) && !\is_string($sourceValue)) {
            return Relation::createFromPrefetched(
                values: $prefetched,
            );
        }

        return Relation::createFromPrefetchedWithBuilder(
            prefetched: $prefetched,
            loaderBuilder: static function (array $criteria, ?int $limit, ?int $offset) use ($manager, $targetTable, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue, $relatedClass): iterable {
                $statement = $manager->connection->select($targetTable)
                    ->distinct()
                    ->innerJoin($throughTable, $throughTable . '.' . $throughSecondLocalKey, $targetTable . '.' . $secondKey)
                    ->where($throughTable . '.' . $firstKey, $sourceValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                if ($limit !== null) {
                    $statement->limit($limit, $offset);
                }

                return $statement->fetchAll($relatedClass, $manager->hydrator);
            },
            countBuilder: static function (array $criteria) use ($manager, $targetTable, $targetPrimaryKey, $throughTable, $throughSecondLocalKey, $secondKey, $firstKey, $sourceValue): int {
                $statement = $manager->connection->count($targetTable)
                    ->column($targetTable . '.' . $targetPrimaryKey)
                    ->distinct()
                    ->innerJoin($throughTable, $throughTable . '.' . $throughSecondLocalKey, $targetTable . '.' . $secondKey)
                    ->where($throughTable . '.' . $firstKey, $sourceValue);

                foreach ($criteria as $extra) {
                    $extra($statement);
                }

                return $statement->count();
            },
        );
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
                    throw ModelException::fromCantFetchWithoutPrimaryKey(
                        modelClass: $metaData->model,
                    );
                }

                return $metaData->key->property;
            }

            return $this->findPropertyByColumn($metaData, $localKey, $relation->property);
        }

        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $relation->property,
        );
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
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $relation->relatedClass,
                );
            }

            return $targetMetaData->key->column;
        }

        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $relation->relatedClass,
            property: $relation->property,
        );
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

        throw ModelException::fromRelationKeyReferencesUnknownColumn(
            modelClass: $metaData->model,
            property: $relationProperty,
            keyKind: 'column',
            keyValue: $column,
            referencedClass: $metaData->model,
        );
    }
}
