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
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Relation\RelationInterface;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Attribute\Unique;
use Tuxxedo\Model\Behavior\BeforeDeleteBehaviorInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Behavior\SoftDeleteBehaviorInterface;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
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
use Tuxxedo\Model\Relation;
use Tuxxedo\Reflection\ClassReflector;
use Tuxxedo\Reflection\PropertyReflectorInterface;

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

        $readonly = $class->reflector->isReadOnly() || \array_any(
            $columns,
            static fn (ModelColumnInterface $column): bool => $column->readonly,
        );

        $behaviors = $this->buildBehaviors($model, $columns);

        return new ModelMetaData(
            model: $model,
            table: $this->getTable($class),
            key: $primaryKey ?? $compositeKey,
            columns: $columns,
            identifiers: $identifiers,
            readonly: $readonly,
            relations: $this->getRelations($class, $columns, $primaryKey),
            behaviors: $behaviors,
        );
    }

    /**
     * @param class-string $modelClass
     * @param non-empty-array<ModelColumnInterface> $columns
     * @return array<string, class-string<BehaviorInterface>>
     *
     * @throws ModelException
     */
    private function buildBehaviors(
        string $modelClass,
        array $columns,
    ): array {
        $behaviors = [];
        $softDeleteProperties = [];

        foreach ($columns as $column) {
            $behaviorClass = $column->attribute->behavior;

            if ($behaviorClass === null) {
                continue;
            }

            $behaviors[$column->property] = $behaviorClass;

            if (\is_a($behaviorClass, SoftDeleteBehaviorInterface::class, true)) {
                $softDeleteProperties[] = $column->property;
            }
        }

        if (\sizeof($softDeleteProperties) > 1) {
            throw ModelException::fromMultipleSoftDeleteColumns(
                modelClass: $modelClass,
                properties: $softDeleteProperties,
            );
        }

        return $behaviors;
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
        ?ModelPrimaryKeyInterface $sourcePrimaryKey,
    ): array {
        $relations = [];
        $sourceColumnNames = \array_map(
            static fn (ModelColumnInterface $column): string => $column->column,
            $columns,
        );

        $parentHasSoftDelete = \array_any(
            $columns,
            static fn (ModelColumnInterface $column): bool => $column->attribute->behavior !== null &&
                \is_a($column->attribute->behavior, SoftDeleteBehaviorInterface::class, true),
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

            if (\sizeof($relatedReflection->getAttributes(Table::class)) === 0) {
                throw ModelException::fromRelatedClassNotAModel(
                    modelClass: $class->name,
                    property: $property->name,
                    relatedClass: $relatedClass,
                );
            }

            $targetColumnNames = $this->getColumnNamesFromReflection($relatedReflection);

            if (\sizeof($targetColumnNames) === 0) {
                throw ModelException::fromHasNoColumns(
                    modelClass: $relatedClass,
                );
            }

            $throughReflection = null;
            $throughColumnNames = null;

            if ($attribute instanceof HasOneThrough || $attribute instanceof HasManyThrough) {
                $throughClass = $attribute->through;

                try {
                    $throughReflection = new \ReflectionClass($throughClass);

                    if (
                        $throughReflection->isAbstract() ||
                        $throughReflection->isTrait() ||
                        $throughReflection->isInterface() ||
                        $throughReflection->isEnum()
                    ) {
                        throw new \ReflectionException();
                    }
                } catch (\ReflectionException) {
                    throw ModelException::fromInvalidThroughClass(
                        modelClass: $class->name,
                        property: $property->name,
                        throughClass: $throughClass,
                    );
                }

                if (\sizeof($throughReflection->getAttributes(Table::class)) === 0) {
                    throw ModelException::fromThroughClassNotAModel(
                        modelClass: $class->name,
                        property: $property->name,
                        throughClass: $throughClass,
                    );
                }

                $throughColumnNames = $this->getColumnNamesFromReflection($throughReflection);

                if (\sizeof($throughColumnNames) === 0) {
                    throw ModelException::fromHasNoColumns(
                        modelClass: $throughClass,
                    );
                }
            }

            $this->validateRelationKeys(
                modelClass: $class->name,
                property: $property->name,
                attribute: $attribute,
                relatedClass: $relatedClass,
                relatedReflection: $relatedReflection,
                sourceColumnNames: $sourceColumnNames,
                targetColumnNames: $targetColumnNames,
                sourcePrimaryKey: $sourcePrimaryKey,
                throughReflection: $throughReflection,
                throughColumnNames: $throughColumnNames,
            );

            $this->validateCascadeConfiguration(
                modelClass: $class->name,
                property: $property->name,
                attribute: $attribute,
                relatedClass: $relatedClass,
                relatedReflection: $relatedReflection,
                parentHasSoftDelete: $parentHasSoftDelete,
            );

            $this->validateRelationPropertyType(
                modelClass: $class->name,
                property: $property,
                attribute: $attribute,
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
     *
     * @throws ModelException
     */
    private function validateColumnCoercer(
        string $modelClass,
        string $property,
        ColumnInterface $attribute,
    ): void {
        if ($attribute->coercer === null) {
            return;
        }

        if (
            !\class_exists($attribute->coercer) ||
            !\is_a($attribute->coercer, CoercerInterface::class, true)
        ) {
            throw ModelException::fromInvalidCoercerClass(
                modelClass: $modelClass,
                property: $property,
                coercerClass: $attribute->coercer,
            );
        }
    }

    /**
     * @param class-string $modelClass
     *
     * @throws ModelException
     */
    private function validateColumnBehavior(
        string $modelClass,
        string $property,
        ColumnInterface $attribute,
    ): void {
        if ($attribute->behavior === null) {
            return;
        }

        if (
            !\class_exists($attribute->behavior) ||
            !\is_a($attribute->behavior, BehaviorInterface::class, true)
        ) {
            throw ModelException::fromInvalidBehaviorClass(
                modelClass: $modelClass,
                property: $property,
                behaviorClass: $attribute->behavior,
            );
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function isColumnNullableInReflection(
        \ReflectionClass $reflection,
        string $columnName,
    ): bool {
        foreach ($reflection->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(ColumnInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (\sizeof($columnAttributes) === 0) {
                continue;
            }

            $columnAttribute = $columnAttributes[0]->newInstance();
            $name = $columnAttribute->name ?? $property->getName();

            if ($name !== $columnName) {
                continue;
            }

            return $property->getType()?->allowsNull() ?? false;
        }

        return false;
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     * @param \ReflectionClass<object> $relatedReflection
     *
     * @throws ModelException
     */
    private function validateCascadeConfiguration(
        string $modelClass,
        string $property,
        RelationInterface $attribute,
        string $relatedClass,
        \ReflectionClass $relatedReflection,
        bool $parentHasSoftDelete,
    ): void {
        $relationType = match (true) {
            $attribute instanceof HasOne => 'HasOne',
            $attribute instanceof HasMany => 'HasMany',
            $attribute instanceof BelongsTo => 'BelongsTo',
            $attribute instanceof BelongsToMany => 'BelongsToMany',
            $attribute instanceof HasOneThrough => 'HasOneThrough',
            $attribute instanceof HasManyThrough => 'HasManyThrough',
            default => $attribute::class,
        };

        if (
            $attribute->onSave === CascadeAction::RESTRICT ||
            $attribute->onSave === CascadeAction::SET_NULL
        ) {
            throw ModelException::fromInvalidCascadeConfiguration(
                modelClass: $modelClass,
                property: $property,
                relationType: $relationType,
                side: 'onSave',
                action: $attribute->onSave,
            );
        }

        if (
            (
                $attribute instanceof BelongsTo ||
                $attribute instanceof BelongsToMany
            ) && (
                $attribute->onDelete === CascadeAction::RESTRICT ||
                $attribute->onDelete === CascadeAction::SET_NULL
            )
        ) {
            throw ModelException::fromInvalidCascadeConfiguration(
                modelClass: $modelClass,
                property: $property,
                relationType: $relationType,
                side: 'onDelete',
                action: $attribute->onDelete,
            );
        }

        if (
            (
                $attribute instanceof HasOne ||
                $attribute instanceof HasMany
            ) &&
            $attribute->onDelete === CascadeAction::SET_NULL &&
            !$this->isColumnNullableInReflection($relatedReflection, $attribute->foreignKey)
        ) {
            throw ModelException::fromSetNullRequiresNullableColumn(
                modelClass: $modelClass,
                property: $property,
                relatedClass: $relatedClass,
                foreignKey: $attribute->foreignKey,
            );
        }

        if ($attribute instanceof HasMany && $attribute->bulkDelete) {
            $this->validateBulkDeleteCompatibility(
                modelClass: $modelClass,
                property: $property,
                attribute: $attribute,
                relatedClass: $relatedClass,
                relatedReflection: $relatedReflection,
            );
        }

        if (
            (
                $attribute instanceof HasOne ||
                $attribute instanceof HasMany ||
                $attribute instanceof BelongsTo
            ) &&
            $attribute->onDelete === CascadeAction::CASCADE
        ) {
            $childHasSoftDelete = $this->hasSoftDeleteBehaviorInReflection($relatedReflection);

            if ($parentHasSoftDelete !== $childHasSoftDelete) {
                throw ModelException::fromSoftDeleteCascadeMismatch(
                    modelClass: $modelClass,
                    property: $property,
                    relatedClass: $relatedClass,
                    parentHasSoftDelete: $parentHasSoftDelete,
                    childHasSoftDelete: $childHasSoftDelete,
                );
            }
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function hasSoftDeleteBehaviorInReflection(
        \ReflectionClass $reflection,
    ): bool {
        foreach ($reflection->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(ColumnInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (\sizeof($columnAttributes) === 0) {
                continue;
            }

            $columnAttribute = $columnAttributes[0]->newInstance();
            $behaviorClass = $columnAttribute->behavior;

            if ($behaviorClass === null) {
                continue;
            }

            if (\is_a($behaviorClass, SoftDeleteBehaviorInterface::class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     * @param \ReflectionClass<object> $relatedReflection
     *
     * @throws ModelException
     */
    private function validateBulkDeleteCompatibility(
        string $modelClass,
        string $property,
        HasMany $attribute,
        string $relatedClass,
        \ReflectionClass $relatedReflection,
    ): void {
        if ($attribute->onDelete !== CascadeAction::CASCADE) {
            throw ModelException::fromBulkDeleteRequiresCascade(
                modelClass: $modelClass,
                property: $property,
            );
        }

        foreach ($relatedReflection->getProperties() as $childProperty) {
            $columnAttributes = $childProperty->getAttributes(ColumnInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (\sizeof($columnAttributes) === 0) {
                continue;
            }

            $columnAttribute = $columnAttributes[0]->newInstance();
            $behaviorClass = $columnAttribute->behavior;

            if ($behaviorClass === null) {
                continue;
            }

            if (\is_a($behaviorClass, BeforeDeleteBehaviorInterface::class, true)) {
                throw ModelException::fromBulkDeleteIncompatibleWithChildBehavior(
                    modelClass: $modelClass,
                    property: $property,
                    relatedClass: $relatedClass,
                    childProperty: $childProperty->getName(),
                    behaviorClass: $behaviorClass,
                );
            }
        }

        foreach ($relatedReflection->getProperties() as $childProperty) {
            $relationAttributes = $childProperty->getAttributes(RelationInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (\sizeof($relationAttributes) === 0) {
                continue;
            }

            $relationAttribute = $relationAttributes[0]->newInstance();

            if ($relationAttribute->onDelete !== CascadeAction::NO_ACTION) {
                throw ModelException::fromBulkDeleteIncompatibleWithChildCascade(
                    modelClass: $modelClass,
                    property: $property,
                    relatedClass: $relatedClass,
                    childProperty: $childProperty->getName(),
                    action: $relationAttribute->onDelete,
                );
            }
        }
    }

    /**
     * @param class-string $modelClass
     *
     * @throws ModelException
     */
    private function validateRelationPropertyType(
        string $modelClass,
        PropertyReflectorInterface $property,
        RelationInterface $attribute,
    ): void {
        $declaredType = $property->getDefaultType();

        if ($declaredType === null) {
            throw ModelException::fromRelationPropertyTypeUnsupported(
                modelClass: $modelClass,
                property: $property->name,
            );
        }

        $expectedType = $attribute instanceof HasMany ||
            $attribute instanceof BelongsToMany ||
            $attribute instanceof HasManyThrough
                ? Relation::class
                : $attribute->related;

        if (!\is_a($expectedType, $declaredType, true)) {
            throw ModelException::fromRelationPropertyTypeMismatch(
                modelClass: $modelClass,
                property: $property->name,
                declaredType: $declaredType,
                expectedType: $expectedType,
            );
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function hasPrimaryKeyFromReflection(
        \ReflectionClass $reflection,
    ): bool {
        foreach ($reflection->getProperties() as $property) {
            if (\sizeof($property->getAttributes(PrimaryKey::class)) !== 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     * @param \ReflectionClass<object> $relatedReflection
     * @param string[] $sourceColumnNames
     * @param string[] $targetColumnNames
     * @param \ReflectionClass<object>|null $throughReflection
     * @param string[]|null $throughColumnNames
     *
     * @throws ModelException
     */
    private function validateRelationKeys(
        string $modelClass,
        string $property,
        RelationInterface $attribute,
        string $relatedClass,
        \ReflectionClass $relatedReflection,
        array $sourceColumnNames,
        array $targetColumnNames,
        ?ModelPrimaryKeyInterface $sourcePrimaryKey,
        ?\ReflectionClass $throughReflection = null,
        ?array $throughColumnNames = null,
    ): void {
        if ($attribute instanceof HasOne) {
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

            if ($attribute->localKey === null && $sourcePrimaryKey === null) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'source',
                );
            }

            return;
        }

        if ($attribute instanceof HasMany) {
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

            if ($attribute->localKey === null && $sourcePrimaryKey === null) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'source',
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

            if ($attribute->ownerKey === null && !$this->hasPrimaryKeyFromReflection($relatedReflection)) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'target',
                );
            }

            return;
        }

        if ($attribute instanceof BelongsToMany) {
            if ($sourcePrimaryKey === null) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'source',
                );
            }

            if (!$this->hasPrimaryKeyFromReflection($relatedReflection)) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'target',
                );
            }

            return;
        }

        if ($attribute instanceof HasOneThrough || $attribute instanceof HasManyThrough) {
            // @todo Cleanup these @var rules, split method?
            /** @var \ReflectionClass<object> $throughReflection */
            /** @var string[] $throughColumnNames */

            if (!\in_array($attribute->firstKey, $throughColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'firstKey',
                    keyValue: $attribute->firstKey,
                    referencedClass: $attribute->through,
                );
            }

            if (!\in_array($attribute->secondKey, $targetColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'secondKey',
                    keyValue: $attribute->secondKey,
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

            if ($attribute->localKey === null && $sourcePrimaryKey === null) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'source',
                );
            }

            if ($attribute->secondLocalKey !== null && !\in_array($attribute->secondLocalKey, $throughColumnNames, true)) {
                throw ModelException::fromRelationKeyReferencesUnknownColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: 'secondLocalKey',
                    keyValue: $attribute->secondLocalKey,
                    referencedClass: $attribute->through,
                );
            }

            if ($attribute->secondLocalKey === null && !$this->hasPrimaryKeyFromReflection($throughReflection)) {
                throw ModelException::fromRelationRequiresPrimaryKey(
                    modelClass: $modelClass,
                    property: $property,
                    side: 'through',
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

            $this->validateColumnCoercer(
                modelClass: $class->name,
                property: $property->name,
                attribute: $propertyColumns[0],
            );

            $this->validateColumnBehavior(
                modelClass: $class->name,
                property: $property->name,
                attribute: $propertyColumns[0],
            );

            $columns[] = new ModelColumn(
                property: $property->name,
                column: $propertyColumns[0]->name ?? $property->name,
                nullable: $property->isNullable(),
                unique: $property->hasAttribute(Unique::class),
                readonly: $property->reflector->isReadOnly(),
                attribute: $propertyColumns[0],
                primaryKey: $foundPrimaryKey !== null
                    ? $primaryKey
                    : null,
                identifier: $identifiers[$property->name] ?? null,
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
