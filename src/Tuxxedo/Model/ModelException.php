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

namespace Tuxxedo\Model;

class ModelException extends \Exception
{
    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidModelClass(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Class does not exist or is not a non-abstract class',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromMissingTableAttribute(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model is missing the #[Table] attribute',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromHasNoColumns(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model does not have any #[Column] attributes',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyMayOnlyHaveOneColumn(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%1$s": Property %1$s::\$%2$s has more than one #[Column] attribute',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelMayOnlyHaveOneKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model defines both a #[PrimaryKey] and #[CompositeKey] but these are mutually exclusive',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromDuplicatePrimaryKey(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%1$s": Models may only define one #[PrimaryKey] (duplicate declared at %1$s::\$%2$s)',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromNoPrimaryKeyOrCompositeKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s", as the model does not have a #[PrimaryKey] or a #[CompositeKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyValueMustBeScalar(
        string $modelClass,
        string $property,
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": The property value for "%s" must be a scalar, but "%s" was given',
                $modelClass,
                $property,
                $actualType,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyValueMustBeIdentifierType(
        string $modelClass,
        string $property,
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": The property value for "%s" must be string|int, but "%s" was given',
                $modelClass,
                $property,
                $actualType,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCantFetchWithoutPrimaryKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the model does not have a #[PrimaryKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCantFetchWithoutCompositeKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the model does not have a #[CompositeKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelNoLongerExists(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the record no longer exists',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelNotFound(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the record does not exist',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCompositeKeyReferencesUnknownColumn(
        string $modelClass,
        string $column,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": #[CompositeKey] references column "%s" which does not exist on the model',
                $modelClass,
                $column,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromNullValueOnNonNullableColumn(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": A NULL value was supplied to property "%s", but it is not marked as nullable',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidRelatedClass(
        string $modelClass,
        string $property,
        string $relatedClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" references "%s" which does not exist or is not a non-abstract class',
                $modelClass,
                $property,
                $relatedClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyMayOnlyHaveOneRelation(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%1$s": Property %1$s::\$%2$s has more than one relation attribute',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $referencedClass
     */
    public static function fromRelationKeyReferencesUnknownColumn(
        string $modelClass,
        string $property,
        string $keyKind,
        string $keyValue,
        string $referencedClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" references %s "%s" which does not exist as a column on "%s"',
                $modelClass,
                $property,
                $keyKind,
                $keyValue,
                $referencedClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromMissingForeignKeyValue(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot hydrate relation "%s" on model "%s": the foreign key value is NULL but the relation property is not nullable',
                $property,
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromMissingRelatedRecord(
        string $modelClass,
        string $property,
        string $relatedClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot hydrate relation "%s" on model "%s": the related record in "%s" does not exist',
                $property,
                $modelClass,
                $relatedClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromRelationNotFoundOnModel(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot resolve relation "%s" on model "%s": unknown relation type',
                $property,
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromRelationRequiresPrimaryKey(
        string $modelClass,
        string $property,
        string $side,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" requires the %s model to define a primary key',
                $modelClass,
                $property,
                $side,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string<\UnitEnum> $enumClass
     */
    public static function fromInvalidEnumValue(
        string $modelClass,
        string $property,
        string $enumClass,
        mixed $value,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot hydrate property "%s" on model "%s": value of type "%s" does not match any case of enum "%s"',
                $property,
                $modelClass,
                \get_debug_type($value),
                $enumClass,
            ),
            previous: $previous,
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyIsNotAColumn(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Property "%s" on model "%s" is not a column',
                $property,
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromRelatedClassNotAModel(
        string $modelClass,
        string $property,
        string $relatedClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" references "%s" which is not a model (missing #[Table] attribute)',
                $modelClass,
                $property,
                $relatedClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $throughClass
     */
    public static function fromInvalidThroughClass(
        string $modelClass,
        string $property,
        string $throughClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Through relation on property "%s" references "%s" which does not exist or is not a non-abstract class',
                $modelClass,
                $property,
                $throughClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $throughClass
     */
    public static function fromThroughClassNotAModel(
        string $modelClass,
        string $property,
        string $throughClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Through relation on property "%s" references "%s" which is not a model (missing #[Table] attribute)',
                $modelClass,
                $property,
                $throughClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidCascadeConfiguration(
        string $modelClass,
        string $property,
        string $relationType,
        string $side,
        CascadeAction $action,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Cascade action "%s" on %s of %s relation %s::$%s is not a valid configuration',
                $modelClass,
                $action->name,
                $side,
                $relationType,
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromSetNullRequiresNullableColumn(
        string $modelClass,
        string $property,
        string $relatedClass,
        string $foreignKey,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Cascade action SET_NULL on property "%s" requires column "%s" on "%s" to be nullable',
                $modelClass,
                $property,
                $foreignKey,
                $relatedClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromRestrictedRelation(
        string $modelClass,
        string $property,
        string $relatedClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot delete model "%s": Relation on property "%s" has dependent rows in "%s" and is marked RESTRICT',
                $modelClass,
                $property,
                $relatedClass,
            ),
        );
    }

    public static function fromImmutableRelation(): self
    {
        return new self(
            message: 'Immutable relations cannot be modified',
        );
    }

    /**
     * @param class-string $attributeClass
     */
    public static function fromEagerLoadingNotYetSupported(
        string $attributeClass,
    ): self {
        return new self(
            message: \sprintf(
                'Eager loading is not yet supported for relations of type "%s"',
                $attributeClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidEagerLoadPath(
        string $modelClass,
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid eager-load path "%s" for model "%s"',
                $path,
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromUnknownEagerLoadRelation(
        string $modelClass,
        string $relationName,
    ): self {
        return new self(
            message: \sprintf(
                'Model "%s" has no relation named "%s"',
                $modelClass,
                $relationName,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromRelationPropertyTypeUnsupported(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" must declare a single named class type',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $expectedType
     */
    public static function fromRelationPropertyTypeMismatch(
        string $modelClass,
        string $property,
        string $declaredType,
        string $expectedType,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" declares type "%s" but expected a type compatible with "%s"',
                $modelClass,
                $property,
                $declaredType,
                $expectedType,
            ),
        );
    }

    /**
     * @param class-string $coercerClass
     */
    public static function fromCoercionFailure(
        string $coercerClass,
        string $expectedType,
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Coercer "%s" cannot coerce value: expected type "%s", got "%s"',
                $coercerClass,
                $expectedType,
                $actualType,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidCoercerClass(
        string $modelClass,
        string $property,
        string $coercerClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Coercer "%s" on property "%s" does not exist or does not implement CoercerInterface',
                $modelClass,
                $coercerClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidBehaviorClass(
        string $modelClass,
        string $property,
        string $behaviorClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Behavior "%s" on property "%s" does not exist or does not implement BehaviorInterface',
                $modelClass,
                $behaviorClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param string[] $properties
     */
    public static function fromMultipleSoftDeleteColumns(
        string $modelClass,
        array $properties,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Multiple columns declare a SoftDeleteBehaviorInterface (%s); a model may only have one soft-delete tombstone',
                $modelClass,
                \join(', ', $properties),
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromSoftDeleteCascadeMismatch(
        string $modelClass,
        string $property,
        string $relatedClass,
        bool $parentHasSoftDelete,
        bool $childHasSoftDelete,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" cascades to "%s", but soft-delete states differ (parent: %s, child: %s) — cascade would silently cross the soft/hard boundary',
                $modelClass,
                $property,
                $relatedClass,
                $parentHasSoftDelete
                    ? 'soft-deletable'
                    : 'not soft-deletable',
                $childHasSoftDelete
                    ? 'soft-deletable'
                    : 'not soft-deletable',
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromBulkDeleteRequiresCascade(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" sets bulkDelete: true but onDelete is not CASCADE',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     * @param class-string $behaviorClass
     */
    public static function fromBulkDeleteIncompatibleWithChildBehavior(
        string $modelClass,
        string $property,
        string $relatedClass,
        string $childProperty,
        string $behaviorClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" sets bulkDelete: true but child "%s" declares BeforeDelete behavior "%s" on property "%s" — bulk DELETE skips per-row behavior dispatch',
                $modelClass,
                $property,
                $relatedClass,
                $behaviorClass,
                $childProperty,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     * @param class-string $relatedClass
     */
    public static function fromBulkDeleteIncompatibleWithChildCascade(
        string $modelClass,
        string $property,
        string $relatedClass,
        string $childProperty,
        CascadeAction $action,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Relation on property "%s" sets bulkDelete: true but child "%s" declares cascade onDelete "%s" on relation "%s" — bulk DELETE skips child cascade dispatch',
                $modelClass,
                $property,
                $relatedClass,
                $action->name,
                $childProperty,
            ),
        );
    }
}
