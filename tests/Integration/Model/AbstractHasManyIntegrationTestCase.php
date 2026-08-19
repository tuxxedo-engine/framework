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

abstract class AbstractHasManyIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();

        $this->seedUser(
            id: 1,
            name: 'Alice',
        );

        $this->seedUser(
            id: 2,
            name: 'Bob',
        );

        $this->seedPost(
            id: 10,
            userId: 1,
            title: 'Alice first',
            status: 'published',
            viewCount: 100,
            rating: '4.50',
        );

        $this->seedPost(
            id: 11,
            userId: 1,
            title: 'Alice second',
            status: 'draft',
            viewCount: 50,
            rating: '3.00',
        );

        $this->seedPost(
            id: 12,
            userId: 1,
            title: 'Alice third',
            status: 'published',
            viewCount: 200,
            rating: '5.00',
        );
    }

    private function seedUser(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();
    }

    private function seedPost(
        int $id,
        int $userId,
        string $title,
        string $status,
        int $viewCount,
        string $rating,
    ): void {
        $this->connection->insert(
            table: 'posts',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->set(column: 'status', value: $status)
            ->set(column: 'viewCount', value: $viewCount)
            ->set(column: 'rating', value: $rating)
            ->execute();
    }

    /**
     * @return Relation<Post>
     */
    private function alicePosts(): Relation
    {
        $user = $this->modelsManager->fetchById(
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
        $bob = $this->modelsManager->fetchById(
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

        $posts->add($newPost);

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

        $posts->add($newPost);
        $posts->remove($newPost);

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
        $posts->add($newPost);

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
            $this->modelsManager->query(User::class)
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
