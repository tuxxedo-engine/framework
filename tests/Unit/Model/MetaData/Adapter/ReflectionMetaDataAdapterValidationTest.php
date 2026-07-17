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

namespace Unit\Model\MetaData\Adapter;

use Fixture\Model\Broken\BarePropertySkipped;
use Fixture\Model\Broken\BelongsToForeignKeyUnknown;
use Fixture\Model\Broken\BelongsToManySourceNoPrimaryKey;
use Fixture\Model\Broken\BelongsToManyTargetNoPrimaryKey;
use Fixture\Model\Broken\BelongsToOwnerKeyUnknown;
use Fixture\Model\Broken\BelongsToTargetNoPrimaryKey;
use Fixture\Model\Broken\BothKeyTypes;
use Fixture\Model\Broken\BothSoftDeleteHasOne;
use Fixture\Model\Broken\BulkDeleteChildBehavior;
use Fixture\Model\Broken\BulkDeleteChildCascade;
use Fixture\Model\Broken\BulkDeleteRequiresCascade;
use Fixture\Model\Broken\CascadeBareTarget;
use Fixture\Model\Broken\CompositeKeyUnknownColumn;
use Fixture\Model\Broken\DuplicatePrimaryKey;
use Fixture\Model\Broken\HasManyForeignKeyUnknown;
use Fixture\Model\Broken\HasManyLocalKeyUnknown;
use Fixture\Model\Broken\HasManyNoPrimaryKey;
use Fixture\Model\Broken\HasOneForeignKeyUnknown;
use Fixture\Model\Broken\HasOneLocalKeyUnknown;
use Fixture\Model\Broken\HasOneNoPrimaryKey;
use Fixture\Model\Broken\IdentifierWithExplicitColumn;
use Fixture\Model\Broken\InvalidCascadeDelete;
use Fixture\Model\Broken\InvalidCascadeSave;
use Fixture\Model\Broken\InvalidRelatedClass;
use Fixture\Model\Broken\InvalidThroughClass;
use Fixture\Model\Broken\MultipleSoftDelete;
use Fixture\Model\Broken\NoColumns;
use Fixture\Model\Broken\NoTable;
use Fixture\Model\Broken\PropertyWithTwoColumns;
use Fixture\Model\Broken\PropertyWithTwoRelations;
use Fixture\Model\Broken\RelatedClassNotAModel;
use Fixture\Model\Broken\RelatedHasNoColumns;
use Fixture\Model\Broken\RelationPropertyTypeMismatch;
use Fixture\Model\Broken\SetNullBareTarget;
use Fixture\Model\Broken\SetNullRequiresNullable;
use Fixture\Model\Broken\SoftDeleteCascadeMismatch;
use Fixture\Model\Broken\ThroughClassNotAModel;
use Fixture\Model\Broken\ThroughFirstKeyUnknown;
use Fixture\Model\Broken\ThroughHasNoColumns;
use Fixture\Model\Broken\ThroughLocalKeyUnknown;
use Fixture\Model\Broken\ThroughNoSourcePrimaryKey;
use Fixture\Model\Broken\ThroughNoThroughPrimaryKey;
use Fixture\Model\Broken\ThroughSecondKeyUnknown;
use Fixture\Model\Broken\ThroughSecondLocalKeyUnknown;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\ModelException;

class ReflectionMetaDataAdapterValidationTest extends TestCase
{
    private ReflectionMetaDataAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ReflectionMetaDataAdapter();
    }

    /**
     * @param class-string $modelClass
     */
    private function assertRejectsModelWithMessage(
        string $modelClass,
        string $needle,
    ): void {
        try {
            $this->adapter->getModel($modelClass);

            self::fail('Expected ModelException was not thrown for ' . $modelClass);
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                $needle,
                $exception->getMessage(),
            );
        }
    }

    public function testRejectsModelWithoutTableAttribute(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: NoTable::class,
            needle: '#[Table]',
        );
    }

    public function testRejectsModelWithNoColumns(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: NoColumns::class,
            needle: '#[Column]',
        );
    }

    public function testRejectsPropertyWithTwoColumnAttributes(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: PropertyWithTwoColumns::class,
            needle: 'more than one #[Column]',
        );
    }

    public function testRejectsDuplicatePrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: DuplicatePrimaryKey::class,
            needle: '#[PrimaryKey]',
        );
    }

    public function testRejectsMultipleSoftDeleteColumns(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MultipleSoftDelete::class,
            needle: 'soft-delete',
        );
    }

    public function testRejectsCompositeKeyReferencingUnknownColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: CompositeKeyUnknownColumn::class,
            needle: 'nonexistent',
        );
    }

    public function testRejectsModelWithBothPrimaryKeyAndCompositeKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BothKeyTypes::class,
            needle: 'mutually exclusive',
        );
    }

    public function testRejectsPropertyWithTwoRelationAttributes(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: PropertyWithTwoRelations::class,
            needle: 'one relation',
        );
    }

    public function testRejectsInvalidRelatedClass(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: InvalidRelatedClass::class,
            needle: 'Relation on property',
        );
    }

    public function testRejectsRelatedClassLackingTableAttribute(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: RelatedClassNotAModel::class,
            needle: 'not a model',
        );
    }

    public function testRejectsInvalidThroughClass(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: InvalidThroughClass::class,
            needle: 'Through relation',
        );
    }

    public function testRejectsThroughClassLackingTableAttribute(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughClassNotAModel::class,
            needle: 'Through relation',
        );
    }

    public function testRejectsInvalidCascadeConfigurationOnSave(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: InvalidCascadeSave::class,
            needle: 'onSave',
        );
    }

    public function testRejectsInvalidCascadeConfigurationOnDeleteForBelongsTo(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: InvalidCascadeDelete::class,
            needle: 'onDelete',
        );
    }

    public function testRejectsSetNullOnNonNullableForeignKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: SetNullRequiresNullable::class,
            needle: 'nullable',
        );
    }

    public function testRejectsSoftDeleteCascadeMismatch(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: SoftDeleteCascadeMismatch::class,
            needle: 'soft-delete',
        );
    }

    public function testRejectsBulkDeleteWithoutCascade(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BulkDeleteRequiresCascade::class,
            needle: 'bulkDelete',
        );
    }

    public function testRejectsBulkDeleteWhenChildHasBeforeDeleteBehavior(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BulkDeleteChildBehavior::class,
            needle: 'bulkDelete',
        );
    }

    public function testRejectsBulkDeleteWhenChildHasCascadeRelation(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BulkDeleteChildCascade::class,
            needle: 'bulkDelete',
        );
    }

    public function testRejectsRelationPropertyTypeMismatchingRelatedClass(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: RelationPropertyTypeMismatch::class,
            needle: 'property',
        );
    }

    public function testRejectsHasOneWithUnknownForeignKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasOneForeignKeyUnknown::class,
            needle: 'foreignKey',
        );
    }

    public function testRejectsHasOneWithUnknownLocalKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasOneLocalKeyUnknown::class,
            needle: 'localKey',
        );
    }

    public function testRejectsHasOneOnSourceWithoutPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasOneNoPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsHasManyWithUnknownForeignKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasManyForeignKeyUnknown::class,
            needle: 'foreignKey',
        );
    }

    public function testRejectsHasManyWithUnknownLocalKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasManyLocalKeyUnknown::class,
            needle: 'localKey',
        );
    }

    public function testRejectsHasManyOnSourceWithoutPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: HasManyNoPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsBelongsToWithUnknownForeignKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BelongsToForeignKeyUnknown::class,
            needle: 'foreignKey',
        );
    }

    public function testRejectsBelongsToWithUnknownOwnerKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BelongsToOwnerKeyUnknown::class,
            needle: 'ownerKey',
        );
    }

    public function testRejectsBelongsToWhenTargetHasNoPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BelongsToTargetNoPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsBelongsToManyWhenSourceHasNoPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BelongsToManySourceNoPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsBelongsToManyWhenTargetHasNoPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: BelongsToManyTargetNoPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsThroughWithUnknownFirstKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughFirstKeyUnknown::class,
            needle: 'firstKey',
        );
    }

    public function testRejectsThroughWithUnknownSecondKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughSecondKeyUnknown::class,
            needle: 'secondKey',
        );
    }

    public function testRejectsThroughWithUnknownLocalKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughLocalKeyUnknown::class,
            needle: 'localKey',
        );
    }

    public function testRejectsThroughOnSourceWithoutPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughNoSourcePrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsThroughWithUnknownSecondLocalKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughSecondLocalKeyUnknown::class,
            needle: 'secondLocalKey',
        );
    }

    public function testRejectsThroughWhenThroughHasNoPrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughNoThroughPrimaryKey::class,
            needle: 'primary key',
        );
    }

    public function testRejectsRelationWhenRelatedClassHasNoColumns(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: RelatedHasNoColumns::class,
            needle: '#[Column]',
        );
    }

    public function testRejectsThroughWhenThroughClassHasNoColumns(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: ThroughHasNoColumns::class,
            needle: '#[Column]',
        );
    }

    public function testBarePropertyWithoutAttributesIsSkipped(): void
    {
        $metaData = $this->adapter->getModel(BarePropertySkipped::class);

        self::assertSame(
            BarePropertySkipped::class,
            $metaData->model,
        );

        self::assertCount(
            1,
            $metaData->columns,
        );
    }

    public function testSetNullNullabilityScanSkipsBarePropertiesOnTarget(): void
    {
        $metaData = $this->adapter->getModel(SetNullBareTarget::class);

        self::assertSame(
            SetNullBareTarget::class,
            $metaData->model,
        );
    }

    public function testSoftDeleteScanSkipsBarePropertiesOnTarget(): void
    {
        $metaData = $this->adapter->getModel(CascadeBareTarget::class);

        self::assertSame(
            CascadeBareTarget::class,
            $metaData->model,
        );
    }

    public function testCascadeAcceptsMatchingSoftDeleteOnBothSides(): void
    {
        $metaData = $this->adapter->getModel(BothSoftDeleteHasOne::class);

        self::assertSame(
            BothSoftDeleteHasOne::class,
            $metaData->model,
        );
    }

    public function testIdentifierWithExplicitColumnMapsToDeclaredColumn(): void
    {
        $metaData = $this->adapter->getModel(IdentifierWithExplicitColumn::class);

        self::assertCount(
            1,
            $metaData->identifiers,
        );

        self::assertSame(
            'external_name',
            $metaData->identifiers[0]->column,
        );
    }
}
