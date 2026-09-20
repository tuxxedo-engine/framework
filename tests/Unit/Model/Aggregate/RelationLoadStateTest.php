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

namespace Unit\Model\Aggregate;

use Fixture\Model\Polymorphic\Article;
use Fixture\Model\Polymorphic\PolyComment;
use Fixture\Model\Polymorphic\PolyTag;
use Fixture\Model\User;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Aggregate\RelationLoadState;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\MetaData\ModelRelation;
use Tuxxedo\Model\MetaData\MorphToRelationMetaData;
use Tuxxedo\Model\Relation;

class RelationLoadStateTest extends TestCase
{
    public function testBelongsToPropertyThatIsUninitializedLazyProxyReportsNotLoaded(): void
    {
        $article = new Article();
        $article->author = (new \ReflectionClass(User::class))->newLazyProxy(
            static fn (): object => new User(),
        );

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->belongsToAuthor(),
            ),
        );
    }

    public function testBelongsToPropertyThatIsAssignedConcreteObjectReportsLoaded(): void
    {
        $article = new Article();
        $article->author = new User();

        self::assertTrue(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->belongsToAuthor(),
            ),
        );
    }

    public function testBelongsToPropertyThatIsNullReportsNotLoaded(): void
    {
        $article = new Article();

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->belongsToAuthor(),
            ),
        );
    }

    public function testMorphManyRelationWithoutMaterializationOrPendingReportsNotLoaded(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->morphManyComments(),
            ),
        );
    }

    public function testMorphManyRelationWithPendingAddReportsLoaded(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $article->comments->add(new PolyComment());

        self::assertTrue(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->morphManyComments(),
            ),
        );
    }

    public function testMorphManyRelationCreatedFromPrefetchedReportsLoaded(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromPrefetched(
            values: [],
            modelClass: PolyComment::class,
        );

        self::assertTrue(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->morphManyComments(),
            ),
        );
    }

    public function testMorphToManyRelationWithoutContentReportsNotLoaded(): void
    {
        $article = new Article();
        $article->tags = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyTag::class,
        );

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $article,
                relation: $this->morphToManyTags(),
            ),
        );
    }

    public function testMorphToRelationWhenPropertyIsUninitializedLazyProxyReportsNotLoaded(): void
    {
        $comment = new PolyComment();
        $comment->commentable = (new \ReflectionClass(Article::class))->newLazyProxy(
            static fn (): object => new Article(),
        );

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $comment,
                relation: $this->morphToCommentable(),
            ),
        );
    }

    public function testMorphToRelationWhenPropertyIsAssignedConcreteObjectReportsLoaded(): void
    {
        $comment = new PolyComment();
        $comment->commentable = new Article();

        self::assertTrue(
            RelationLoadState::isLoaded(
                model: $comment,
                relation: $this->morphToCommentable(),
            ),
        );
    }

    public function testMorphToRelationWhenPropertyIsNullReportsNotLoaded(): void
    {
        $comment = new PolyComment();

        self::assertFalse(
            RelationLoadState::isLoaded(
                model: $comment,
                relation: $this->morphToCommentable(),
            ),
        );
    }

    private function belongsToAuthor(): ModelRelation
    {
        return new ModelRelation(
            property: 'author',
            relatedClass: User::class,
            nullable: true,
            attribute: new BelongsTo(
                related: User::class,
                foreignKey: 'user_id',
            ),
        );
    }

    private function morphManyComments(): ModelRelation
    {
        return new ModelRelation(
            property: 'comments',
            relatedClass: PolyComment::class,
            nullable: true,
            attribute: new MorphMany(
                related: PolyComment::class,
                typeColumn: 'commentable_type',
                idColumn: 'commentable_id',
            ),
            typeColumn: 'commentable_type',
            idColumn: 'commentable_id',
        );
    }

    private function morphToManyTags(): ModelRelation
    {
        return new ModelRelation(
            property: 'tags',
            relatedClass: PolyTag::class,
            nullable: true,
            attribute: new MorphToMany(
                related: PolyTag::class,
                table: 'poly_taggables',
                typeColumn: 'taggable_type',
                idColumn: 'taggable_id',
                foreignKey: 'tag_id',
            ),
            typeColumn: 'taggable_type',
            idColumn: 'taggable_id',
        );
    }

    private function morphToCommentable(): MorphToRelationMetaData
    {
        return new MorphToRelationMetaData(
            property: 'commentable',
            nullable: true,
            attribute: new MorphTo(
                typeColumn: 'commentable_type',
                idColumn: 'commentable_id',
            ),
            typeColumn: 'commentable_type',
            idColumn: 'commentable_id',
            idColumns: [
                'commentable_id',
            ],
        );
    }
}
