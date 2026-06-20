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
