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

namespace Tuxxedo\Model\MetaData\Adapter;

use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Identifier;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\RelationInterface;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Attribute\Unique;
use Tuxxedo\Model\MetaData\ModelColumn;
use Tuxxedo\Model\MetaData\ModelColumnInterface;
use Tuxxedo\Model\MetaData\ModelCompositeKey;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelIdentifier;
use Tuxxedo\Model\MetaData\ModelIdentifierInterface;
use Tuxxedo\Model\MetaData\ModelMetaData;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKey;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Reflection\ClassReflector;

class ReflectionMetaDataAdapter implements MetaDataAdapterInterface
{
    /**
     * @param class-string $model
     *
     * @throws ModelException
     */
    public function getModel(
        string $model,
    ): ModelMetaDataInterface {
        try {
            $class = new ClassReflector(
                reflector: new \ReflectionClass($model),
            );

            if (
                $class->reflector->isAbstract() ||
                $class->reflector->isTrait() ||
                $class->reflector->isInterface() ||
                $class->reflector->isEnum()
            ) {
                throw new \ReflectionException();
            }
        } catch (\ReflectionException) {
            throw ModelException::fromInvalidModelClass(
                modelClass: $model,
            );
        }

        $primaryKey = null;
        $identifiers = [];
        $columns = $this->getColumns($class, $primaryKey, $identifiers);
        $compositeKey = $this->getCompositeKey($class, $columns);

        if ($compositeKey !== null) {
            foreach ($compositeKey->properties as $compositeKeyProperty) {
                $found = false;

                foreach ($columns as $column) {
                    if ($column->property === $compositeKeyProperty) {
                        $found = true;

                        break;
                    }
                }

                if (!$found) {
                    throw ModelException::fromCompositeKeyReferencesUnknownColumn(
                        modelClass: $class->name,
                        column: $compositeKeyProperty,
                    );
                }
            }
        }

        if ($primaryKey !== null && $compositeKey !== null) {
            throw ModelException::fromModelMayOnlyHaveOneKey(
                modelClass: $class->name,
            );
        }

        return new ModelMetaData(
            model: $model,
            table: $this->getTable($class),
            key: $primaryKey ?? $compositeKey,
            columns: $columns,
            identifiers: $identifiers,
        );
    }

    /**
     * @param non-empty-array<ModelColumnInterface> $columns
     */
    private function getCompositeKey(
        ClassReflector $class,
        array $columns,
    ): ?ModelCompositeKeyInterface {
        if (!$class->hasAttribute(CompositeKey::class)) {
            return null;
        }

        $declaredProperties = $class->getAttribute(CompositeKey::class)->columns;
        $resolvedColumns = [];

        foreach ($declaredProperties as $declaredProperty) {
            foreach ($columns as $column) {
                if ($column->property === $declaredProperty) {
                    $resolvedColumns[] = $column->column;

                    break;
                }
            }
        }

        return new ModelCompositeKey(
            properties: $declaredProperties,
            columns: $resolvedColumns !== []
                ? $resolvedColumns
                : $declaredProperties,
        );
    }

    /**
     * @throws ModelException
     */
    private function getTable(
        ClassReflector $class,
    ): string {
        if (!$class->hasAttribute(Table::class)) {
            throw ModelException::fromMissingTableAttribute(
                modelClass: $class->name,
            );
        }

        return $class->getAttribute(Table::class)->name;
    }

    /**
     * @param ModelIdentifierInterface[] $identifiers
     * @return non-empty-array<ModelColumnInterface>
     *
     * @throws ModelException
     */
    private function getColumns(
        ClassReflector $class,
        ?ModelPrimaryKeyInterface &$primaryKey,
        array &$identifiers,
    ): array {
        $columns = [];

        foreach ($class->properties() as $property) {
            $foundPrimaryKey = null;
            $propertyColumns = \iterator_to_array($property->getAttributes(ColumnInterface::class));
            $propertyColumnsCount = \sizeof($propertyColumns);

            if ($propertyColumnsCount === 0) {
                continue;
            }

            if ($propertyColumnsCount > 1) {
                throw ModelException::fromPropertyMayOnlyHaveOneColumn(
                    modelClass: $class->name,
                    property: $property->name,
                );
            }

            if ($property->hasAttribute(PrimaryKey::class)) {
                if ($primaryKey !== null) {
                    throw ModelException::fromDuplicatePrimaryKey(
                        modelClass: $class->name,
                        property: $property->name,
                    );
                }

                $foundPrimaryKey = $property->getAttribute(PrimaryKey::class);

                $primaryKey = new ModelPrimaryKey(
                    property: $property->name,
                    column: $foundPrimaryKey->column ?? $property->name,
                    autoIncrement: $foundPrimaryKey->autoIncrement,
                );
            }

            if ($property->hasAttribute(Identifier::class)) {
                $identifier = $property->getAttribute(Identifier::class);

                if ($identifier->column !== null) {
                    $identifier = new ModelIdentifier($identifier->column);
                } else {
                    $identifier = new ModelIdentifier($property->name);
                }

                $identifiers[$property->name] = $identifier;
            }

            $relations = [];

            // @todo ??? Validate relations
            foreach ($property->getAttributes(RelationInterface::class) as $relation) {
                $relations[] = $relation;
            }

            $columns[] = new ModelColumn(
                property: $property->name,
                column: $propertyColumns[0]->name ?? $property->name,
                nullable: $property->isNullable(),
                unique: $property->hasAttribute(Unique::class),
                attribute: $propertyColumns[0],
                primaryKey: $foundPrimaryKey !== null
                    ? $primaryKey
                    : null,
                identifier: $identifiers[$property->name] ?? null,
                relations: $relations,
            );
        }

        if (\sizeof($columns) === 0) {
            throw ModelException::fromHasNoColumns(
                modelClass: $class->name,
            );
        }

        $identifiers = \array_values($identifiers);

        return $columns;
    }
}
