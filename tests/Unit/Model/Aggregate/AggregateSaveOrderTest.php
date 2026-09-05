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

use Fixture\Model\Category;
use Fixture\Model\Country;
use Fixture\Model\Polymorphic\Article;
use Fixture\Model\Polymorphic\Avatar;
use Fixture\Model\Polymorphic\Employee;
use Fixture\Model\Polymorphic\PolyComment;
use Fixture\Model\Polymorphic\PolyTag;
use Fixture\Model\Post;
use Fixture\Model\Profile;
use Fixture\Model\Role;
use Fixture\Model\Setting;
use Fixture\Model\User;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Aggregate\AggregateEntityCollector;
use Tuxxedo\Model\Aggregate\AggregateSaveOrder;
use Tuxxedo\Model\Aggregate\CollectedEntity;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class AggregateSaveOrderTest extends TestCase
{
    private AggregateSaveOrder $order;

    private AggregateEntityCollector $collector;

    private MetaData $metaData;

    protected function setUp(): void
    {
        $this->metaData = new MetaData(
            adapter: new ReflectionMetaDataAdapter(),
        );
        $this->collector = new AggregateEntityCollector(metaData: $this->metaData);
        $this->order = new AggregateSaveOrder();
    }

    public function testSortReturnsSingleRootAsIs(): void
    {
        $article = new Article();

        $sorted = $this->order->sort(
            $this->collector->collect($article),
        );

        self::assertCount(1, $sorted);
        self::assertSame($article, $sorted[0]->entity);
    }

    public function testSortPlacesBelongsToTargetBeforeParent(): void
    {
        $country = new Country();
        $user = new User();
        $user->country = $country;

        $sorted = $this->order->sort(
            $this->collector->collect($user),
        );

        self::assertSame(
            [
                $country,
                $user,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesHasOneChildAfterRoot(): void
    {
        $user = new User();
        $profile = new Profile();
        $user->profile = $profile;

        $sorted = $this->order->sort(
            $this->collector->collect($user),
        );

        self::assertSame(
            [
                $user,
                $profile,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesHasManyChildrenAfterRoot(): void
    {
        $user = new User();
        $user->posts = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Post::class,
        );

        $postA = new Post();
        $postB = new Post();
        $user->posts->add($postA);
        $user->posts->add($postB);

        $sorted = $this->order->sort(
            $this->collector->collect($user),
        );

        self::assertSame(
            [
                $user,
                $postA,
                $postB,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesBelongsToManyTargetsBeforeParent(): void
    {
        $user = new User();
        $user->roles = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Role::class,
        );

        $roleA = new Role();
        $roleB = new Role();
        $user->roles->add($roleA);
        $user->roles->add($roleB);

        $sorted = $this->order->sort(
            $this->collector->collect($user),
        );

        self::assertSame(
            [
                $roleA,
                $roleB,
                $user,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesMorphOneChildAfterOwner(): void
    {
        $employee = new Employee();
        $avatar = new Avatar();
        $employee->avatar = $avatar;

        $sorted = $this->order->sort(
            $this->collector->collect($employee),
        );

        self::assertSame(
            [
                $employee,
                $avatar,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesMorphManyChildrenAfterOwner(): void
    {
        $article = new Article();
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $commentA = new PolyComment();
        $commentB = new PolyComment();
        $article->comments->add($commentA);
        $article->comments->add($commentB);

        $sorted = $this->order->sort(
            $this->collector->collect($article),
        );

        self::assertSame(
            [
                $article,
                $commentA,
                $commentB,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesMorphToManyTargetsBeforeParent(): void
    {
        $article = new Article();
        $article->tags = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyTag::class,
        );

        $tagA = new PolyTag();
        $tagB = new PolyTag();
        $article->tags->add($tagA);
        $article->tags->add($tagB);

        $sorted = $this->order->sort(
            $this->collector->collect($article),
        );

        self::assertSame(
            [
                $tagA,
                $tagB,
                $article,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortPlacesMorphToTargetBeforeParent(): void
    {
        $article = new Article();
        $comment = new PolyComment();
        $comment->commentable = $article;

        $sorted = $this->order->sort(
            $this->collector->collect($comment),
        );

        self::assertSame(
            [
                $article,
                $comment,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortBidirectionalOwnerChildProducesStableOrder(): void
    {
        $user = new User();
        $post = new Post();
        $post->author = $user;
        $user->posts = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Post::class,
        );
        $user->posts->add($post);

        $sorted = $this->order->sort(
            $this->collector->collect($user),
        );

        self::assertSame(
            [
                $user,
                $post,
            ],
            $this->entities($sorted),
        );
    }

    public function testSortThrowsForEntityLevelCycle(): void
    {
        $catA = new Category();
        $catA->name = 'A';
        $catB = new Category();
        $catB->name = 'B';
        $catA->parent = $catB;
        $catB->parent = $catA;

        try {
            $this->order->sort(
                $this->collector->collect($catA),
            );

            self::fail('Expected ModelException for entity-level cycle');
        } catch (ModelException $exception) {
            self::assertStringContainsString('cycle', \strtolower($exception->getMessage()));
        }
    }

    public function testSortIgnoresSelfLoopEdgesFromEntityReferencingItself(): void
    {
        $category = new Category();
        $category->name = 'self';
        $category->parent = $category;

        $sorted = $this->order->sort(
            $this->collector->collect($category),
        );

        self::assertCount(1, $sorted);
        self::assertSame($category, $sorted[0]->entity);
    }

    public function testSortThrowsForCompositeKeyEntityInGraph(): void
    {
        $setting = new Setting();

        try {
            $this->order->sort(
                $this->collector->collect($setting),
            );

            self::fail('Expected ModelException for composite-key entity in aggregate');
        } catch (ModelException $exception) {
            self::assertStringContainsString('composite-key', \strtolower($exception->getMessage()));
        }
    }

    /**
     * @param list<CollectedEntity> $sorted
     * @return list<object>
     */
    private function entities(
        array $sorted,
    ): array {
        return \array_map(
            static fn (CollectedEntity $c): object => $c->entity,
            $sorted,
        );
    }
}
