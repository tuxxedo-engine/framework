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
use Tuxxedo\Model\Aggregate\AggregateEntityCollector;
use Tuxxedo\Model\Aggregate\CollectedEntity;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\Relation;

class AggregateEntityCollectorTest extends TestCase
{
    private AggregateEntityCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new AggregateEntityCollector(
            metaData: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
        );
    }

    public function testCollectRootWithNoTouchedRelationsReturnsSingleEntry(): void
    {
        $article = new Article();

        $collected = $this->collector->collect($article);

        self::assertCount(1, $collected);
        self::assertSame('', $collected[0]->path);
        self::assertSame($article, $collected[0]->entity);
    }

    public function testCollectBelongsToLoadedAsConcreteObjectIsRecursed(): void
    {
        $article = new Article();
        $article->author = new User();

        $collected = $this->collector->collect($article);
        $paths = $this->paths($collected);

        self::assertSame(
            [
                '',
                'author',
            ],
            $paths,
        );
    }

    public function testCollectMorphManyWithPendingAddsRecursesEachChild(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $article->comments->add(new PolyComment());
        $article->comments->add(new PolyComment());

        $collected = $this->collector->collect($article);
        $paths = $this->paths($collected);

        self::assertSame(
            [
                '',
                'comments.0',
                'comments.1',
            ],
            $paths,
        );
    }

    public function testCollectMorphToManyPrefetchedIsRecursed(): void
    {
        $article = new Article();
        $tag = new PolyTag();
        $article->tags = Relation::createFromPrefetched(
            values: [
                $tag,
            ],
            modelClass: PolyTag::class,
        );

        $collected = $this->collector->collect($article);
        $paths = $this->paths($collected);

        self::assertSame(
            [
                '',
                'tags.0',
            ],
            $paths,
        );
    }

    public function testCollectDeepGraphIncludesGrandchildren(): void
    {
        $article = new Article();
        $article->author = new User();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $comment = new PolyComment();
        $comment->commentable = $article;
        $article->comments->add($comment);

        $collected = $this->collector->collect($article);
        $paths = $this->paths($collected);

        self::assertContains('', $paths);
        self::assertContains('author', $paths);
        self::assertContains('comments.0', $paths);
    }

    public function testCollectBidirectionalCycleDoesNotRepeat(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $comment = new PolyComment();
        $comment->commentable = $article;
        $article->comments->add($comment);

        $collected = $this->collector->collect($article);
        $entities = \array_map(
            static fn (CollectedEntity $c): object => $c->entity,
            $collected,
        );

        self::assertSame(2, \sizeof($collected));
        self::assertSame(
            [
                $article,
                $comment,
            ],
            $entities,
        );
    }

    public function testCollectSelfReferenceIsRecordedOnce(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $comment = new PolyComment();
        $comment->commentable = $comment;
        $article->comments->add($comment);

        $collected = $this->collector->collect($article);

        self::assertCount(2, $collected);
        self::assertSame($comment, $collected[1]->entity);
    }

    public function testCollectSkipsRelationsThatAreNotLoaded(): void
    {
        $article = new Article();

        $collected = $this->collector->collect($article);

        self::assertCount(1, $collected);
    }

    public function testCollectSkipsUntouchedRelationCreatedFromBuilder(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $collected = $this->collector->collect($article);

        self::assertCount(1, $collected);
        self::assertSame('', $collected[0]->path);
    }

    public function testCollectRecordsMorphToTargetUnderPropertyPath(): void
    {
        $comment = new PolyComment();
        $comment->commentable = new Article();

        $collected = $this->collector->collect($comment);
        $paths = $this->paths($collected);

        self::assertSame(
            [
                '',
                'commentable',
            ],
            $paths,
        );
    }

    /**
     * @param list<CollectedEntity> $collected
     * @return list<string>
     */
    private function paths(
        array $collected,
    ): array {
        return \array_map(
            static fn (CollectedEntity $c): string => $c->path,
            $collected,
        );
    }
}
