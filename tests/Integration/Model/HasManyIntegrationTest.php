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

namespace Integration\Model;

use Fixture\Model\Post;
use Fixture\Model\User;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Model\Relation;

class HasManyIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (2, 'Bob', 'bob@example.test', 1, 0, 0.0)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (10, 1, 'Alice first', '', 'published', 100, '4.50')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (11, 1, 'Alice second', '', 'draft', 50, '3.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (12, 1, 'Alice third', '', 'published', 200, '5.00')",
            native: true,
        );
    }

    /**
     * @return Relation<Post>
     */
    private function alicePosts(): Relation
    {
        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        return $user->posts;
    }

    public function testHasManyRelationCountReflectsDbRows(): void
    {
        self::assertSame(
            3,
            $this->alicePosts()->count(),
        );
    }

    public function testHasManyRelationFirstReturnsPost(): void
    {
        $post = $this->alicePosts()->first();

        self::assertInstanceOf(
            Post::class,
            $post,
        );
    }

    public function testHasManyRelationIterationYieldsAllPosts(): void
    {
        $titles = [];

        foreach ($this->alicePosts() as $post) {
            $titles[] = $post->title;
        }

        \sort($titles);

        self::assertSame(
            [
                'Alice first',
                'Alice second',
                'Alice third',
            ],
            $titles,
        );
    }

    public function testHasManyRelationEmptyCollectionForUserWithoutPosts(): void
    {
        $bob = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 2,
        );

        self::assertInstanceOf(
            Relation::class,
            $bob->posts,
        );

        self::assertSame(
            0,
            $bob->posts->count(),
        );

        self::assertNull(
            $bob->posts->first(),
        );
    }

    public function testHasManyRelationFilteredByWhere(): void
    {
        $published = $this->alicePosts()->where(
            column: 'status',
            value: 'published',
        );

        self::assertSame(
            2,
            $published->count(),
        );
    }

    public function testHasManyRelationOrderByViewCountDescending(): void
    {
        $ordered = $this->alicePosts()->orderBy(
            column: 'viewCount',
            direction: OrderDirection::DESC,
        );

        $viewCounts = [];

        /** @var Post $post */
        foreach ($ordered->fetchAll() as $post) {
            $viewCounts[] = $post->viewCount;
        }

        self::assertSame(
            [
                200,
                100,
                50,
            ],
            $viewCounts,
        );
    }

    public function testHasManyRelationAddPushesToPendingAdds(): void
    {
        $posts = $this->alicePosts();

        $newPost = new Post();
        $newPost->title = 'Alice new';
        $newPost->body = '';

        $posts->add(item: $newPost);

        self::assertContains(
            $newPost,
            $posts->pendingAdds,
        );
    }

    public function testHasManyRelationRemoveAfterAddCancelsPendingAdd(): void
    {
        $posts = $this->alicePosts();

        $newPost = new Post();
        $newPost->title = 'ephemeral';

        $posts->add(item: $newPost);
        $posts->remove(item: $newPost);

        self::assertNotContains(
            $newPost,
            $posts->pendingAdds,
        );

        self::assertNotContains(
            $newPost,
            $posts->pendingRemoves,
        );
    }

    public function testHasManyRelationClearPendingResetsBothQueues(): void
    {
        $posts = $this->alicePosts();

        $newPost = new Post();
        $posts->add(item: $newPost);

        $posts->clearPending();

        self::assertSame(
            [],
            $posts->pendingAdds,
        );

        self::assertSame(
            [],
            $posts->pendingRemoves,
        );
    }

    public function testEagerLoadingViaQueryWithPrefetchesPosts(): void
    {
        $users = \iterator_to_array(
            $this->modelsManager->query(class: User::class)
                ->with(
                    with: [
                        'posts' => static fn (Relation $relation): Relation => $relation,
                    ],
                )
                ->fetchAll(),
        );

        $users = \array_values($users);
        $alice = null;

        foreach ($users as $user) {
            if ($user->id === 1) {
                $alice = $user;

                break;
            }
        }

        self::assertNotNull(
            $alice,
        );

        self::assertInstanceOf(
            Relation::class,
            $alice->posts,
        );

        self::assertTrue(
            $alice->posts->isMaterialized(),
        );

        self::assertSame(
            3,
            $alice->posts->count(),
        );
    }
}
