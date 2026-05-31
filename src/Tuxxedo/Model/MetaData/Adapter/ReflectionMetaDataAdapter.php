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
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
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
use Tuxxedo\Model\MetaData\ModelRelation;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Reflection\ClassReflector;

// @todo HasMany and BelongsToMany is not properly validated
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
            relations: $this->getRelations($class, $columns),
        );
    }

    /**
     * @param non-empty-array<ModelColumnInterface> $columns
     * @return ModelRelationInterface[]
     *
     * @throws ModelException
     */
    private function getRelations(
        ClassReflector $class,
        array $columns,
    ): array {
        $relations = [];
        $sourceColumnNames = \array_map(
            static fn (ModelColumnInterface $column): string => $column->column,
            $columns,
        );

        foreach ($class->properties() as $property) {
            $columnAttributes = \iterator_to_array($property->getAttributes(ColumnInterface::class));

            if (\sizeof($columnAttributes) !== 0) {
                continue;
            }

            $relationAttributes = \iterator_to_array($property->getAttributes(RelationInterface::class));
            $relationAttributesCount = \sizeof($relationAttributes);

            if ($relationAttributesCount === 0) {
                continue;
            }

            if ($relationAttributesCount > 1) {
                throw ModelException::fromPropertyMayOnlyHaveOneRelation(
                    modelClass: $class->name,
                    property: $property->name,
                );
            }

            $attribute = $relationAttributes[0];
            $relatedClass = $attribute->related;

            try {
                $relatedReflection = new \ReflectionClass($relatedClass);

                if (
                    $relatedReflection->isAbstract() ||
                    $relatedReflection->isTrait() ||
                    $relatedReflection->isInterface() ||
                    $relatedReflection->isEnum()
                ) {
                    throw new \ReflectionException();
                }
            } catch (\ReflectionException) {
                throw ModelException::fromInvalidRelatedClass(
                    modelClass: $class->name,
                    property: $property->name,
                    relatedClass: $relatedClass,
                );
            }

            $targetColumnNames = $this->getColumnNamesFromReflection($relatedReflection);

            $this->validateRelationKeys(
                modelClass: $class->name,
                property: $property->name,
                attribute: $attribute,
                relatedClass: $relatedClass,
                sourceColumnNames: $sourceColumnNames,
                targetColumnNames: $targetColumnNames,
            );

            $relations[] = new ModelRelation(
                property: $property->name,
                relatedClass: $relatedClass,
                nullable: $property->isNullable(),
                attribute: $attribute,
            );
        }

        return $relations;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return string[]
     */
    private function getColumnNamesFromReflection(
        \ReflectionClass $reflection,
    ): array {
        $names = [];

        foreach ($reflection->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(ColumnInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (\sizeof($columnAttributes) === 0) {
                continue;
            }

            $columnAttribute = $columnAttributes[0]->newInstance();
            $names[] = $columnAttribute->name ?? $property->getName();
        }

        return $names;
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     * @param string[] $sourceColumnNames
     * @param string[] $targetColumnNames
     *
     * @throws ModelException
     */
    private function validateRelationKeys(
        string $modelClass,
        string $property,
        RelationInterface $attribute,
        string $relatedClass,
        array $sourceColumnNames,
        array $targetColumnNames,
    ): void {
        if ($attribute instanceof HasOne || $attribute instanceof HasMany) {
            if (!\in_array($attribute->foreignKey, $targetColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'foreignKey',
                    keyValue: $attribute->foreignKey,
                    referencedClass: $relatedClass,
                );
            }

            if ($attribute->localKey !== null && !\in_array($attribute->localKey, $sourceColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'localKey',
                    keyValue: $attribute->localKey,
                    referencedClass: $modelClass,
                );
            }

            return;
        }

        if ($attribute instanceof BelongsTo) {
            if (!\in_array($attribute->foreignKey, $sourceColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'foreignKey',
                    keyValue: $attribute->foreignKey,
                    referencedClass: $modelClass,
                );
            }

            if ($attribute->ownerKey !== null && !\in_array($attribute->ownerKey, $targetColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'ownerKey',
                    keyValue: $attribute->ownerKey,
                    referencedClass: $relatedClass,
                );
            }

            return;
        }
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
