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
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
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

// @todo JOIN-mode hydration for HasOneThrough / HasManyThrough — current path runs two queries plus full through-row hydration to extract secondLocalKey; collapse into a single SELECT far.* INNER JOIN through ON through.secondLocalKey = far.secondKey WHERE through.firstKey = ? (LIMIT 1 for HasOneThrough). Use DISTINCT for HasManyThrough load + count to preserve current IN-clause dedupe semantics (HasOneThrough doesn't need it since LIMIT 1 caps the result). Required CountBuilder::distinct() now exists. Retire collectThroughKeys + resolveThroughSecondLocalKeyProperty when migrated; add resolveThroughSecondLocalKeyColumn helper for the JOIN column resolution. Cuts per-row through hydration cost and one round-trip per countLoader invocation
// @todo Through hydration dedupe trade-off — JOIN + DISTINCT preserves "each far row at most once" semantics that current whereIn-based path provides. Once whereIn() learns subquery form, evaluate switching to WHERE secondKey IN (SELECT secondLocalKey FROM through WHERE firstKey = ?) which gives the same dedupe via IN semantics without needing DISTINCT — may be faster on some engines depending on optimizer
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

        $relationInstance = Relation::createFromLoader(
            loader: static fn (): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectBuilderInterface $builder) use ($targetColumn, $sourceValue): void {
                    $builder->where($targetColumn, $sourceValue);
                },
            ),
            countLoader: static fn (): int => $manager->connection->count($targetTable)
                ->where($targetColumn, $sourceValue)
                ->count(),
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

        $relationInstance = Relation::createFromLoader(
            loader: static fn (): iterable => $manager->findAll(
                $relatedClass,
                static function (SelectBuilderInterface $builder) use ($pivotTable, $pivotForeignKey, $pivotLocalKey, $targetTable, $targetPrimaryKey, $sourceValue): void {
                    $builder
                        ->innerJoin($pivotTable, $pivotTable . '.' . $pivotForeignKey, $targetTable . '.' . $targetPrimaryKey)
                        ->where($pivotTable . '.' . $pivotLocalKey, $sourceValue);
                },
            ),
            countLoader: static fn (): int => $manager->connection->count($pivotTable)
                ->where($pivotLocalKey, $sourceValue)
                ->count(),
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
        $throughClass = $attribute->through;
        $throughFirstKey = $attribute->firstKey;
        $throughSecondLocalKeyProperty = $this->resolveThroughSecondLocalKeyProperty($attribute);
        $relatedClass = $relation->relatedClass;
        $secondKey = $attribute->secondKey;
        $targetTable = $manager->metaData->getModel($relatedClass)->table;

        $relationInstance = Relation::createFromLoader(
            loader: static function () use ($manager, $throughClass, $throughFirstKey, $throughSecondLocalKeyProperty, $relatedClass, $secondKey, $sourceValue): iterable {
                $throughIds = self::collectThroughKeys($manager, $throughClass, $throughFirstKey, $throughSecondLocalKeyProperty, $sourceValue);

                if (\sizeof($throughIds) === 0) {
                    return [];
                }

                return $manager->findAll(
                    $relatedClass,
                    static function (SelectBuilderInterface $builder) use ($secondKey, $throughIds): void {
                        $builder->whereIn($secondKey, $throughIds);
                    },
                );
            },
            countLoader: static function () use ($manager, $throughClass, $throughFirstKey, $throughSecondLocalKeyProperty, $targetTable, $secondKey, $sourceValue): int {
                $throughIds = self::collectThroughKeys($manager, $throughClass, $throughFirstKey, $throughSecondLocalKeyProperty, $sourceValue);

                if (\sizeof($throughIds) === 0) {
                    return 0;
                }

                return $manager->connection->count($targetTable)
                    ->whereIn($secondKey, $throughIds)
                    ->count();
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

        $throughIds = self::collectThroughKeys(
            $manager,
            $attribute->through,
            $attribute->firstKey,
            $this->resolveThroughSecondLocalKeyProperty($attribute),
            $sourceValue,
        );

        if (\sizeof($throughIds) === 0) {
            throw ModelException::fromMissingRelatedRecord(
                modelClass: $sourceMetaData->model,
                property: $relation->property,
                relatedClass: $relation->relatedClass,
            );
        }

        $secondKey = $attribute->secondKey;

        $result = $manager->findFirst(
            $relation->relatedClass,
            static function (SelectBuilderInterface $builder) use ($secondKey, $throughIds): void {
                $builder->whereIn($secondKey, $throughIds);
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
     * @param class-string $throughClass
     * @return array<int, string|int|float|bool>
     */
    private static function collectThroughKeys(
        ModelsManagerInterface $manager,
        string $throughClass,
        string $throughFirstKey,
        string $throughSecondLocalKeyProperty,
        string|int|float|bool $sourceValue,
    ): array {
        $ids = [];

        foreach (
            $manager->findAll(
                $throughClass,
                static function (SelectBuilderInterface $builder) use ($throughFirstKey, $sourceValue): void {
                    $builder->where($throughFirstKey, $sourceValue);
                },
            ) as $through
        ) {
            $value = PropertyReflector::createFromObject($through, $throughSecondLocalKeyProperty)->getValue($through);

            if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !\is_bool($value)) {
                continue;
            }

            $ids[] = $value;
        }

        return $ids;
    }

    private function resolveThroughSecondLocalKeyProperty(
        HasOneThrough|HasManyThrough $attribute,
    ): string {
        $throughMetaData = $this->modelsManager->metaData->getModel($attribute->through);
        $secondLocalKey = $attribute->secondLocalKey;

        if ($secondLocalKey === null) {
            if (!$throughMetaData->key instanceof ModelPrimaryKeyInterface) {
                throw ModelException::fromCantFetchWithoutPrimaryKey(
                    modelClass: $attribute->through,
                );
            }

            return $throughMetaData->key->property;
        }

        foreach ($throughMetaData->columns as $column) {
            if ($column->column === $secondLocalKey) {
                return $column->property;
            }
        }

        throw ModelException::fromRelationKeyReferencesUnknownColumn(
            modelClass: $attribute->through,
            property: $secondLocalKey,
            keyKind: 'secondLocalKey',
            keyValue: $secondLocalKey,
            referencedClass: $attribute->through,
        );
    }

    private function loadSingleRelation(
        ModelMetaDataInterface $sourceMetaData,
        ModelRelationInterface $relation,
        string|int|float|bool $sourceValue,
    ): object {
        $targetColumn = $this->resolveTargetColumn($relation);

        $result = $this->modelsManager->findFirst(
            $relation->relatedClass,
            static function (SelectBuilderInterface $builder) use ($targetColumn, $sourceValue): void {
                $builder->where($targetColumn, $sourceValue);
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
