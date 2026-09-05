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

use Fixture\Model\Broken\MorphManyNoSourcePrimaryKey;
use Fixture\Model\Broken\MorphOneCascadeConfigured;
use Fixture\Model\Broken\MorphOneLocalKeyUnknown;
use Fixture\Model\Broken\MorphOnePropertyMismatchClass;
use Fixture\Model\Broken\MorphOnePropertyTypeMismatch;
use Fixture\Model\Broken\MorphOneRelatedHasNoColumns;
use Fixture\Model\Broken\MorphOneRelatedIsInterface;
use Fixture\Model\Broken\MorphOneRelatedNotAModel;
use Fixture\Model\Broken\MorphOneTargetMissingIdColumn;
use Fixture\Model\Broken\MorphOneTargetMissingTypeColumn;
use Fixture\Model\Broken\MorphOneTypeMapEntryInvalid;
use Fixture\Model\Broken\MorphOneTypeMapEntryNotAModel;
use Fixture\Model\Broken\MorphToIdColumnUnknown;
use Fixture\Model\Broken\MorphToManyNoSourcePrimaryKey;
use Fixture\Model\Broken\MorphToManyTargetNotAModel;
use Fixture\Model\Broken\MorphToOnDeleteRestrict;
use Fixture\Model\Broken\MorphToPropertyIsClassNotObject;
use Fixture\Model\Broken\MorphToPropertyMustBeObject;
use Fixture\Model\Broken\MorphToTypeColumnUnknown;
use Fixture\Model\Broken\MorphToTypeMapEntryInvalid;
use Fixture\Model\Broken\MorphToTypeMapEntryNotAModel;
use Fixture\Model\Polymorphic\Article;
use Fixture\Model\Polymorphic\Avatar;
use Fixture\Model\Polymorphic\Employee;
use Fixture\Model\Polymorphic\MappedArticle;
use Fixture\Model\Polymorphic\MappedComment;
use Fixture\Model\Polymorphic\PolyComment;
use Fixture\Model\Polymorphic\PolyTag;
use Fixture\Model\Polymorphic\Product;
use Fixture\Model\Polymorphic\Video;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\ModelException;

class ReflectionMetaDataAdapterPolymorphicTest extends TestCase
{
    private ReflectionMetaDataAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ReflectionMetaDataAdapter();
    }

    public function testMorphToRelationLandsInSeparateCollection(): void
    {
        $meta = $this->adapter->getModel(PolyComment::class);

        self::assertSame(
            [],
            $meta->relations,
        );
        self::assertCount(
            1,
            $meta->morphToRelations,
        );
    }

    public function testMorphToRelationCapturesTypeAndIdColumns(): void
    {
        $meta = $this->adapter->getModel(PolyComment::class);
        $morphTo = $meta->morphToRelations[0];

        self::assertSame('commentable', $morphTo->property);
        self::assertSame('commentable_type', $morphTo->typeColumn);
        self::assertSame('commentable_id', $morphTo->idColumn);
        self::assertNull($morphTo->typeMap);
        self::assertInstanceOf(MorphTo::class, $morphTo->attribute);
    }

    public function testMorphManyRelationLandsInRelationsWithMorphMetadata(): void
    {
        $meta = $this->adapter->getModel(Article::class);

        $comments = null;

        foreach ($meta->relations as $relation) {
            if ($relation->property === 'comments') {
                $comments = $relation;

                break;
            }
        }

        self::assertNotNull($comments);
        self::assertSame(PolyComment::class, $comments->relatedClass);
        self::assertSame('commentable_type', $comments->typeColumn);
        self::assertSame('commentable_id', $comments->idColumn);
        self::assertInstanceOf(MorphMany::class, $comments->attribute);
    }

    public function testMorphToManyRelationCapturesPivotShape(): void
    {
        $meta = $this->adapter->getModel(Article::class);

        $tags = null;

        foreach ($meta->relations as $relation) {
            if ($relation->property === 'tags') {
                $tags = $relation;

                break;
            }
        }

        self::assertNotNull($tags);
        self::assertSame(PolyTag::class, $tags->relatedClass);
        self::assertSame('taggable_type', $tags->typeColumn);
        self::assertSame('taggable_id', $tags->idColumn);
        self::assertInstanceOf(MorphToMany::class, $tags->attribute);
    }

    public function testMorphOneRelationDiscoveredOnBothOwners(): void
    {
        $employeeMeta = $this->adapter->getModel(Employee::class);
        $productMeta = $this->adapter->getModel(Product::class);

        $employeeAvatar = null;

        foreach ($employeeMeta->relations as $relation) {
            if ($relation->property === 'avatar') {
                $employeeAvatar = $relation;

                break;
            }
        }

        $productAvatar = null;

        foreach ($productMeta->relations as $relation) {
            if ($relation->property === 'avatar') {
                $productAvatar = $relation;

                break;
            }
        }

        self::assertNotNull($employeeAvatar);
        self::assertNotNull($productAvatar);
        self::assertInstanceOf(MorphOne::class, $employeeAvatar->attribute);
        self::assertInstanceOf(MorphOne::class, $productAvatar->attribute);
        self::assertSame(Avatar::class, $employeeAvatar->relatedClass);
    }

    public function testVideoAlsoDiscoversMorphManyComments(): void
    {
        $meta = $this->adapter->getModel(Video::class);

        $found = false;

        foreach ($meta->relations as $relation) {
            if ($relation->attribute instanceof MorphMany) {
                $found = true;

                break;
            }
        }

        self::assertTrue($found);
    }

    public function testRejectsMorphToWithUnknownTypeColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToTypeColumnUnknown::class,
            needle: 'typeColumn',
        );
    }

    public function testRejectsMorphToWithUnknownIdColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToIdColumnUnknown::class,
            needle: 'idColumn',
        );
    }

    public function testRejectsMorphToTypeMapEntryPointingAtNonModel(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToTypeMapEntryNotAModel::class,
            needle: '#[Table]',
        );
    }

    public function testRejectsMorphOneWithRelatedClassNotAModel(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneRelatedNotAModel::class,
            needle: '#[Table]',
        );
    }

    public function testRejectsMorphOneWhenTargetMissingTypeColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneTargetMissingTypeColumn::class,
            needle: 'typeColumn',
        );
    }

    public function testRejectsMorphOneWhenTargetMissingIdColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneTargetMissingIdColumn::class,
            needle: 'idColumn',
        );
    }

    public function testRejectsPolymorphicRelationWithCascadeAction(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneCascadeConfigured::class,
            needle: 'not a valid configuration',
        );
    }

    public function testRejectsMorphManyWithoutSourcePrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphManyNoSourcePrimaryKey::class,
            needle: 'primary',
        );
    }

    public function testRejectsMorphToManyPointingAtNonModel(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToManyTargetNotAModel::class,
            needle: '#[Table]',
        );
    }

    public function testRejectsMorphToWithNonObjectProperty(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToPropertyMustBeObject::class,
            needle: 'must be typed as "object"',
        );
    }

    public function testRejectsMorphOneWithMismatchedPropertyType(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOnePropertyTypeMismatch::class,
            needle: 'must declare a single named class type',
        );
    }

    public function testRejectsMorphToWithOnDeleteRestrict(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToOnDeleteRestrict::class,
            needle: 'not a valid configuration',
        );
    }

    public function testMorphToTypeMapCapturedInMetadata(): void
    {
        $meta = $this->adapter->getModel(MappedComment::class);

        self::assertCount(1, $meta->morphToRelations);
        self::assertSame(
            [
                'article' => MappedArticle::class,
            ],
            $meta->morphToRelations[0]->typeMap,
        );
    }

    public function testInverseMorphTypeMapCapturedInMetadata(): void
    {
        $meta = $this->adapter->getModel(MappedArticle::class);

        $comments = null;

        foreach ($meta->relations as $relation) {
            if ($relation->property === 'comments') {
                $comments = $relation;

                break;
            }
        }

        self::assertNotNull($comments);
        self::assertSame(
            [
                'article' => MappedArticle::class,
            ],
            $comments->typeMap,
        );
    }

    public function testRejectsMorphOneWhereRelatedIsInterface(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneRelatedIsInterface::class,
            needle: 'does not exist or is not a non-abstract class',
        );
    }

    public function testRejectsMorphOneWhereRelatedHasNoColumns(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneRelatedHasNoColumns::class,
            needle: 'does not have any #[Column]',
        );
    }

    public function testRejectsMorphOneWithUnknownLocalKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneLocalKeyUnknown::class,
            needle: 'localKey',
        );
    }

    public function testRejectsMorphToManyWithoutSourcePrimaryKey(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToManyNoSourcePrimaryKey::class,
            needle: 'primary',
        );
    }

    public function testRejectsInverseMorphWithInvalidTypeMapEntry(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneTypeMapEntryInvalid::class,
            needle: 'does not exist or is not a non-abstract class',
        );
    }

    public function testRejectsInverseMorphWithTypeMapEntryNotAModel(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOneTypeMapEntryNotAModel::class,
            needle: '#[Table]',
        );
    }

    public function testRejectsMorphToWithInvalidTypeMapEntry(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToTypeMapEntryInvalid::class,
            needle: 'does not exist or is not a non-abstract class',
        );
    }

    public function testRejectsMorphToWithConcreteClassProperty(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphToPropertyIsClassNotObject::class,
            needle: 'must be typed as "object"',
        );
    }

    public function testRejectsMorphOneWithMismatchedClassProperty(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: MorphOnePropertyMismatchClass::class,
            needle: 'declares type',
        );
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
}
