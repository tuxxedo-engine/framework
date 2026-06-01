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
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\ModelsManagerInterface;
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
        $metaData = $this->metaData->getModel($className);

        $propertyValues = [];

        foreach ($metaData->columns as $column) {
            if (\array_key_exists($column->column, $values)) {
                $propertyValues[$column->property] = $values[$column->column];
            }
        }

        $model = $this->hydrator->hydrate($className, $propertyValues);

        $this->attachRelations($model, $metaData);

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

            // @todo HasMany and BelongsToMany — pending Collection wrapper design
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

        if ($attribute instanceof HasOne) {
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

        if ($attribute instanceof HasOne) {
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
