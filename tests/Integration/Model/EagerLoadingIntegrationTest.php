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

use Fixture\Model\Country;
use Fixture\Model\Post;
use Fixture\Model\Profile;
use Fixture\Model\User;
use Tuxxedo\Model\Relation;

class EagerLoadingIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCountriesTable();
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();
        $this->createRolesTable();
        $this->createUserRolePivot();

        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (2, 'Denmark', 'DK')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Bob', 'bob@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (3, 'Carla', 'carla@example.test', 1, 0, 0.0, 2)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO profiles (id, user_id, bio) VALUES (10, 1, 'Alice bio')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO profiles (id, user_id, bio) VALUES (11, 2, 'Bob bio')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO profiles (id, user_id, bio) VALUES (12, 3, 'Carla bio')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (100, 1, 'Alice one', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (101, 1, 'Alice two', '', 'draft', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (102, 2, 'Bob one', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (103, 3, 'Carla one', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (200, 'ADMIN', 'Administrator', 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (201, 'EDITOR', 'Editor', 2)",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (1, 200)',
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (1, 201)',
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (2, 201)',
            native: true,
        );
    }

    /**
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null> $with
     * @return list<User>
     */
    private function fetchUsersWith(array $with): array
    {
        return \array_values(
            \iterator_to_array(
                $this->modelsManager->query(class: User::class)
                    ->orderBy(column: 'id')
                    ->with(with: $with)
                    ->fetchAll(),
            ),
        );
    }

    public function testEagerLoadHasOneAttachesProfileWithoutLazyProxy(): void
    {
        $users = $this->fetchUsersWith(
            [
                'profile' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        self::assertCount(
            3,
            $users,
        );

        $aliceProfile = $users[0]->profile;
        $bobProfile = $users[1]->profile;
        $carlaProfile = $users[2]->profile;

        self::assertInstanceOf(
            Profile::class,
            $aliceProfile,
        );

        self::assertInstanceOf(
            Profile::class,
            $bobProfile,
        );

        self::assertInstanceOf(
            Profile::class,
            $carlaProfile,
        );

        self::assertSame(
            'Alice bio',
            $aliceProfile->bio,
        );

        self::assertSame(
            'Bob bio',
            $bobProfile->bio,
        );

        self::assertSame(
            'Carla bio',
            $carlaProfile->bio,
        );
    }

    public function testEagerLoadBelongsToAttachesCountryOnUser(): void
    {
        $users = $this->fetchUsersWith(
            [
                'country' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $aliceCountry = $users[0]->country;
        $bobCountry = $users[1]->country;
        $carlaCountry = $users[2]->country;

        self::assertInstanceOf(
            Country::class,
            $aliceCountry,
        );

        self::assertInstanceOf(
            Country::class,
            $bobCountry,
        );

        self::assertInstanceOf(
            Country::class,
            $carlaCountry,
        );

        self::assertSame(
            'Sweden',
            $aliceCountry->name,
        );

        self::assertSame(
            'Sweden',
            $bobCountry->name,
        );

        self::assertSame(
            'Denmark',
            $carlaCountry->name,
        );
    }

    public function testEagerLoadHasManyPrefetchesPostsAsMaterialized(): void
    {
        $users = $this->fetchUsersWith(
            [
                'posts' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $alicePosts = $users[0]->posts;
        $bobPosts = $users[1]->posts;
        $carlaPosts = $users[2]->posts;

        self::assertInstanceOf(
            Relation::class,
            $alicePosts,
        );

        self::assertInstanceOf(
            Relation::class,
            $bobPosts,
        );

        self::assertInstanceOf(
            Relation::class,
            $carlaPosts,
        );

        self::assertTrue(
            $alicePosts->isMaterialized(),
        );

        self::assertSame(
            2,
            $alicePosts->count(),
        );

        self::assertSame(
            1,
            $bobPosts->count(),
        );

        self::assertSame(
            1,
            $carlaPosts->count(),
        );
    }

    public function testEagerLoadHasManyRespectsClosureConstraint(): void
    {
        $users = $this->fetchUsersWith(
            [
                'posts' => static fn (Relation $relation): Relation => $relation->where(
                    column: 'status',
                    value: 'published',
                ),
            ],
        );

        $alicePosts = $users[0]->posts;

        self::assertInstanceOf(
            Relation::class,
            $alicePosts,
        );

        self::assertSame(
            1,
            $alicePosts->count(),
        );

        $firstPost = $alicePosts->first();

        self::assertInstanceOf(
            Post::class,
            $firstPost,
        );

        self::assertSame(
            'Alice one',
            $firstPost->title,
        );
    }

    public function testEagerLoadBelongsToManyPrefetchesRolesAsMaterialized(): void
    {
        $users = $this->fetchUsersWith(
            [
                'roles' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $aliceRoles = $users[0]->roles;
        $bobRoles = $users[1]->roles;
        $carlaRoles = $users[2]->roles;

        self::assertInstanceOf(
            Relation::class,
            $aliceRoles,
        );

        self::assertInstanceOf(
            Relation::class,
            $bobRoles,
        );

        self::assertInstanceOf(
            Relation::class,
            $carlaRoles,
        );

        self::assertTrue(
            $aliceRoles->isMaterialized(),
        );

        self::assertSame(
            2,
            $aliceRoles->count(),
        );

        self::assertSame(
            1,
            $bobRoles->count(),
        );

        self::assertSame(
            0,
            $carlaRoles->count(),
        );
    }

    public function testEagerLoadHasManyThroughPrefetchesPostsAcrossThroughRows(): void
    {
        $countries = \array_values(
            \iterator_to_array(
                $this->modelsManager->query(class: Country::class)
                    ->orderBy(column: 'id')
                    ->with(
                        with: [
                            'posts' => static fn (Relation $relation): Relation => $relation,
                        ],
                    )
                    ->fetchAll(),
            ),
        );

        self::assertCount(
            2,
            $countries,
        );

        $swedenPosts = $countries[0]->posts;
        $denmarkPosts = $countries[1]->posts;

        self::assertInstanceOf(
            Relation::class,
            $swedenPosts,
        );

        self::assertInstanceOf(
            Relation::class,
            $denmarkPosts,
        );

        self::assertTrue(
            $swedenPosts->isMaterialized(),
        );

        self::assertSame(
            3,
            $swedenPosts->count(),
        );

        self::assertSame(
            1,
            $denmarkPosts->count(),
        );
    }

    public function testEagerLoadHasOneThroughAttachesTargetOnSource(): void
    {
        $countries = \array_values(
            \iterator_to_array(
                $this->modelsManager->query(class: Country::class)
                    ->orderBy(column: 'id')
                    ->with(
                        with: [
                            'firstPost' => static fn (Relation $relation): Relation => $relation,
                        ],
                    )
                    ->fetchAll(),
            ),
        );

        self::assertInstanceOf(
            Post::class,
            $countries[0]->firstPost,
        );

        self::assertInstanceOf(
            Post::class,
            $countries[1]->firstPost,
        );
    }

    public function testEagerLoadNestedRelationsHydrateChildren(): void
    {
        $users = $this->fetchUsersWith(
            [
                'posts' => static fn (Relation $relation): Relation => $relation,
                'posts.author' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $alicePosts = $users[0]->posts;

        self::assertInstanceOf(
            Relation::class,
            $alicePosts,
        );

        $firstPost = $alicePosts->first();

        self::assertInstanceOf(
            Post::class,
            $firstPost,
        );

        $author = $firstPost->author;

        self::assertInstanceOf(
            User::class,
            $author,
        );

        self::assertSame(
            'Alice',
            $author->name,
        );
    }

    public function testEagerLoadWithEmptyParentSetDoesNotError(): void
    {
        $users = \array_values(
            \iterator_to_array(
                $this->modelsManager->query(class: User::class)
                    ->where(column: 'name', value: 'Nobody')
                    ->with(
                        with: [
                            'profile' => static fn (Relation $relation): Relation => $relation,
                            'posts' => static fn (Relation $relation): Relation => $relation,
                            'roles' => static fn (Relation $relation): Relation => $relation,
                        ],
                    )
                    ->fetchAll(),
            ),
        );

        self::assertSame(
            [],
            $users,
        );
    }

    public function testEagerLoadHasManyForParentWithNoChildrenYieldsEmptyMaterializedRelation(): void
    {
        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (99, 'Nemo', 'nemo@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $users = $this->fetchUsersWith(
            [
                'posts' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $nemo = null;

        foreach ($users as $user) {
            if ($user->id === 99) {
                $nemo = $user;

                break;
            }
        }

        self::assertNotNull(
            $nemo,
        );

        $nemoPosts = $nemo->posts;

        self::assertInstanceOf(
            Relation::class,
            $nemoPosts,
        );

        self::assertTrue(
            $nemoPosts->isMaterialized(),
        );

        self::assertSame(
            0,
            $nemoPosts->count(),
        );
    }

    public function testEagerLoadNullableHasOneForParentWithoutChildYieldsNull(): void
    {
        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (77, 'Orphan', 'orphan@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $users = $this->fetchUsersWith(
            [
                'profile' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        $orphan = null;

        foreach ($users as $user) {
            if ($user->id === 77) {
                $orphan = $user;

                break;
            }
        }

        self::assertNotNull(
            $orphan,
        );

        self::assertNull(
            $orphan->profile,
        );
    }
}
