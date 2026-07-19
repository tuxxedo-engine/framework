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

abstract class AbstractEagerLoadingIntegrationTestCase extends AbstractModelIntegrationTestCase
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

        $this->seedCountry(
            id: 1,
            name: 'Sweden',
            code: 'SE',
        );

        $this->seedCountry(
            id: 2,
            name: 'Denmark',
            code: 'DK',
        );

        $this->seedUser(
            id: 1,
            name: 'Alice',
            countryId: 1,
        );

        $this->seedUser(
            id: 2,
            name: 'Bob',
            countryId: 1,
        );

        $this->seedUser(
            id: 3,
            name: 'Carla',
            countryId: 2,
        );

        $this->seedProfile(
            id: 10,
            userId: 1,
            bio: 'Alice bio',
        );

        $this->seedProfile(
            id: 11,
            userId: 2,
            bio: 'Bob bio',
        );

        $this->seedProfile(
            id: 12,
            userId: 3,
            bio: 'Carla bio',
        );

        $this->seedPost(
            id: 100,
            userId: 1,
            title: 'Alice one',
            status: 'published',
        );

        $this->seedPost(
            id: 101,
            userId: 1,
            title: 'Alice two',
            status: 'draft',
        );

        $this->seedPost(
            id: 102,
            userId: 2,
            title: 'Bob one',
            status: 'published',
        );

        $this->seedPost(
            id: 103,
            userId: 3,
            title: 'Carla one',
            status: 'published',
        );

        $this->seedRole(
            id: 200,
            key: 'ADMIN',
            label: 'Administrator',
            sortOrder: 1,
        );

        $this->seedRole(
            id: 201,
            key: 'EDITOR',
            label: 'Editor',
            sortOrder: 2,
        );

        $this->seedUserRole(
            userId: 1,
            roleId: 200,
        );

        $this->seedUserRole(
            userId: 1,
            roleId: 201,
        );

        $this->seedUserRole(
            userId: 2,
            roleId: 201,
        );
    }

    private function seedCountry(
        int $id,
        string $name,
        string $code,
    ): void {
        $this->connection->insert(
            table: 'countries',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'code', value: $code)
            ->execute();
    }

    private function seedUser(
        int $id,
        string $name,
        ?int $countryId = null,
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
            ->set(column: 'country_id', value: $countryId)
            ->execute();
    }

    private function seedProfile(
        int $id,
        int $userId,
        string $bio,
    ): void {
        $this->connection->insert(
            table: 'profiles',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'bio', value: $bio)
            ->execute();
    }

    private function seedPost(
        int $id,
        int $userId,
        string $title,
        string $status,
    ): void {
        $this->connection->insert(
            table: 'posts',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->set(column: 'status', value: $status)
            ->set(column: 'viewCount', value: 0)
            ->set(column: 'rating', value: '0.00')
            ->execute();
    }

    private function seedRole(
        int $id,
        string $key,
        string $label,
        int $sortOrder,
    ): void {
        $this->connection->insert(
            table: 'roles',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'key', value: $key)
            ->set(column: 'label', value: $label)
            ->set(column: 'sortOrder', value: $sortOrder)
            ->execute();
    }

    private function seedUserRole(
        int $userId,
        int $roleId,
    ): void {
        $this->connection->insert(
            table: 'user_role',
        )
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'role_id', value: $roleId)
            ->execute();
    }

    /**
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null> $with
     * @return list<User>
     */
    private function fetchUsersWith(array $with): array
    {
        return \array_values(
            \iterator_to_array(
                $this->modelsManager->query(User::class)
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
                $this->modelsManager->query(Country::class)
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
                $this->modelsManager->query(Country::class)
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
                $this->modelsManager->query(User::class)
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
        $this->seedUser(
            id: 99,
            name: 'Nemo',
            countryId: 1,
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
        $this->seedUser(
            id: 77,
            name: 'Orphan',
            countryId: 1,
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
