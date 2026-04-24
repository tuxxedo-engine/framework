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
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\MetaData\ModelCompositeKey;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
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
        $compositeKey = $this->getCompositeKey($class);
        $columns = $this->getColumns($class, $primaryKey);

        if ($primaryKey !== null && $compositeKey !== null) {
            throw ModelException::fromModelMayOnlyHaveOneKey(
                modelClass: $class->name,
            );
        }

        return new ModelMetaData(
            model: $model,
            table: $this->getTable($class),
            columns: $columns,
            key: $primaryKey ?? $compositeKey,
        );
    }

    private function getCompositeKey(
        ClassReflector $class,
    ): ?ModelCompositeKeyInterface {
        if ($class->hasAttribute(CompositeKey::class)) {
            return new ModelCompositeKey(
                columns: $class->getAttribute(CompositeKey::class)->columns,
            );
        }

        return null;
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
     * @return non-empty-array<string, ColumnInterface>
     *
     * @throws ModelException
     */
    private function getColumns(
        ClassReflector $class,
        ?ModelPrimaryKeyInterface &$primaryKey,
    ): array {
        $columns = [];
        $foundPrimaryKey = null;
        $foundPrimaryKeyColumn = null;

        foreach ($class->properties() as $property) {
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
                if ($foundPrimaryKey !== null) {
                    throw ModelException::fromDuplicatePrimaryKey(
                        modelClass: $class->name,
                        property: $property->name,
                    );
                }

                $foundPrimaryKey = $property->getAttribute(PrimaryKey::class);
                $foundPrimaryKeyColumn = $property->name;
            }

            $columns[$property->name] = $propertyColumns[0];
        }

        if (\sizeof($columns) === 0) {
            throw ModelException::fromHasNoColumns(
                modelClass: $class->name,
            );
        }

        if ($foundPrimaryKey !== null && $foundPrimaryKeyColumn !== null) {
            $primaryKey = new ModelPrimaryKey(
                column: $foundPrimaryKey->column ?? $foundPrimaryKeyColumn,
                autoIncrement: $foundPrimaryKey->autoIncrement,
            );
        }

        $primaryKey ??= null;

        return $columns;
    }
}
