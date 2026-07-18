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

use Fixture\Model\BulkParent;
use Fixture\Model\Category;
use Fixture\Model\Comment;
use Fixture\Model\Country;
use Fixture\Model\NullableThroughOwner;
use Fixture\Model\Post;
use Fixture\Model\PostStatus;
use Fixture\Model\Profile;
use Fixture\Model\Region;
use Fixture\Model\Role;
use Fixture\Model\StrictChild;
use Fixture\Model\StrictOwner;
use Fixture\Model\StrictThroughOwner;
use Fixture\Model\Tag;
use Fixture\Model\User;
use Fixture\Model\Warehouse;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class HydratorIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAllFixtureTables();

        $this->connection->query(
            sql: 'CREATE TABLE strict_owners (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE strict_profiles (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'owner_id INTEGER NOT NULL, ' .
                'handle TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE strict_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'owner_id INTEGER NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE regions (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE branches (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'region_id INTEGER NOT NULL, ' .
                'warehouse_id INTEGER NOT NULL' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE warehouses (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE bulk_parents (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE bulk_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'parent_id INTEGER NOT NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE nullable_through_owners (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'nullable_ref_id INTEGER NULL' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE strict_through_owners (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'nullable_ref_id INTEGER NULL' .
                ')',
            native: true,
        );
    }

    private function seedRegionGraph(): void
    {
        $this->connection->query(
            sql: "INSERT INTO regions (id, name) VALUES (1, 'Nordic')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO warehouses (id, name) VALUES (100, 'Depot A')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO warehouses (id, name) VALUES (101, 'Depot B')",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO branches (id, region_id, warehouse_id) VALUES (10, 1, 100)',
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO branches (id, region_id, warehouse_id) VALUES (11, 1, 101)',
            native: true,
        );
    }

    private function seedBaseGraph(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
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
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (10, 1, 'Alice one', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (11, 1, 'Alice two', '', 'draft', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (12, 1, 'Alice three', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (13, 2, 'Bob one', '', 'published', 0, '0.00')",
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
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (202, 'VIEWER', 'Viewer', 3)",
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
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (1, 202)',
            native: true,
        );
    }

    public function testHydrateUserBasicScalarColumns(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.test',
                'isActive' => 1,
                'postCount' => 5,
                'score' => 12.5,
            ],
        );

        self::assertSame(
            1,
            $user->id,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );

        self::assertSame(
            'alice@example.test',
            $user->email,
        );

        self::assertTrue(
            $user->isActive,
        );

        self::assertSame(
            5,
            $user->postCount,
        );

        self::assertSame(
            12.5,
            $user->score,
        );
    }

    public function testHydrateUserWithNullOptionalColumns(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => '',
                'lastLoginAt' => null,
                'createdAt' => null,
                'updatedAt' => null,
            ],
        );

        self::assertNull(
            $user->lastLoginAt,
        );

        self::assertNull(
            $user->createdAt,
        );

        self::assertNull(
            $user->updatedAt,
        );
    }

    public function testHydrateUserWithMissingColumnPreservesDefault(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertSame(
            '',
            $user->email,
        );

        self::assertTrue(
            $user->isActive,
        );

        self::assertSame(
            0,
            $user->postCount,
        );

        self::assertSame(
            0.0,
            $user->score,
        );
    }

    public function testHydrateUserCreatedAtCoercedToDateTimeImmutable(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'createdAt' => '2026-07-16',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $user->createdAt,
        );

        self::assertSame(
            '2026-07-16',
            $user->createdAt->format('Y-m-d'),
        );
    }

    public function testHydrateUserAttachesHasOneProfileAsLazyProxyWhenIdPresent(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Profile::class,
            $user->profile,
        );
    }

    public function testHydrateUserAttachesHasOneProfileAsNullWhenIdIsNull(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => null,
                'name' => 'Alice',
            ],
        );

        self::assertNull(
            $user->profile,
        );
    }

    public function testHydrateUserAttachesHasManyPostsAsRelation(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );
    }

    public function testHydrateUserAttachesBelongsToManyRolesAsRelation(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );
    }

    public function testHydrateRecordsDirtySnapshotSoModelIsNotDirty(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.test',
            ],
        );

        self::assertTrue(
            $this->modelsManager->dirtyTracker->hasSnapshot($user),
        );

        $meta = $this->modelsManager->metaData->getModel(User::class);

        self::assertFalse(
            $this->modelsManager->dirtyTracker->isDirty(
                model: $user,
                metaData: $meta,
            ),
        );
    }

    public function testHydrateProfileWithForeignKeySnakeCaseColumn(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 42,
                'bio' => 'Some bio',
            ],
        );

        self::assertSame(
            42,
            $profile->userId,
        );

        self::assertSame(
            'Some bio',
            $profile->bio,
        );
    }

    public function testHydrateProfileBlobRoundTrips(): void
    {
        $binary = "\x00\x01\x02\xff\xferaw bytes";

        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'avatar' => $binary,
            ],
        );

        self::assertSame(
            $binary,
            $profile->avatar,
        );
    }

    public function testHydrateProfileJsonColumnDecodedToAssocArray(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'settings' => '{"theme":"dark","notifications":true}',
            ],
        );

        self::assertSame(
            [
                'theme' => 'dark',
                'notifications' => true,
            ],
            $profile->settings,
        );
    }

    public function testHydrateProfileDateColumnCoerced(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'birthDate' => '1990-05-20',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $profile->birthDate,
        );

        self::assertSame(
            '1990-05-20',
            $profile->birthDate->format('Y-m-d'),
        );
    }

    public function testHydratePostEnumerationCoercedToEnumInstance(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'status' => 'published',
            ],
        );

        self::assertSame(
            PostStatus::PUBLISHED,
            $post->status,
        );
    }

    public function testHydratePostDecimalColumnPreservedAsString(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'rating' => '4.75',
            ],
        );

        self::assertSame(
            '4.75',
            $post->rating,
        );
    }

    public function testHydratePostBigIntegerColumnPreservedAsInt(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'viewCount' => 12345678901,
            ],
        );

        self::assertSame(
            12345678901,
            $post->viewCount,
        );
    }

    public function testHydrateCommentSoftDeleteColumnCoerced(): void
    {
        $comment = $this->modelsManager->hydrator->hydrate(
            className: Comment::class,
            values: [
                'id' => 1,
                'post_id' => 1,
                'user_id' => 1,
                'body' => '',
                'deletedAt' => '2026-07-16',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $comment->deletedAt,
        );
    }

    public function testHydrateRoleTimeColumnCoerced(): void
    {
        $role = $this->modelsManager->hydrator->hydrate(
            className: Role::class,
            values: [
                'id' => 1,
                'key' => 'ADMIN',
                'label' => 'Administrator',
                'sortOrder' => 10,
                'startsAt' => '09:00:00',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $role->startsAt,
        );

        self::assertSame(
            '09:00:00',
            $role->startsAt->format('H:i:s'),
        );
    }

    public function testHydrateRoleTinyIntegerAndCharPreserved(): void
    {
        $role = $this->modelsManager->hydrator->hydrate(
            className: Role::class,
            values: [
                'id' => 1,
                'key' => 'ADM',
                'label' => 'Admin',
                'sortOrder' => 100,
            ],
        );

        self::assertSame(
            'ADM',
            $role->key,
        );

        self::assertSame(
            100,
            $role->sortOrder,
        );
    }

    public function testHydrateTagIdentifierPreservedAsString(): void
    {
        $tag = $this->modelsManager->hydrator->hydrate(
            className: Tag::class,
            values: [
                'id' => 1,
                'slug' => 'php-tips',
                'name' => 'PHP Tips',
                'category' => 'DEV',
            ],
        );

        self::assertSame(
            'php-tips',
            $tag->slug,
        );

        self::assertSame(
            'DEV',
            $tag->category,
        );
    }

    public function testHydrateCategorySelfReferentialParentAttachment(): void
    {
        $category = $this->modelsManager->hydrator->hydrate(
            className: Category::class,
            values: [
                'id' => 5,
                'parent_id' => 2,
                'name' => 'Subcategory',
                'depth' => 1,
            ],
        );

        self::assertSame(
            2,
            $category->parentId,
        );

        self::assertInstanceOf(
            Relation::class,
            $category->children,
        );
    }

    public function testHydrateThrowsWhenNonScalarValueProvidedToCoercedColumn(): void
    {
        $this->expectException(ModelException::class);

        $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'status' => new \stdClass(),
            ],
        );
    }

    public function testHydratorThrowsWhenNonNullableBelongsToForeignKeyIsNull(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_children (id, owner_id, label) VALUES (1, NULL, 'orphan')",
            native: true,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByIdentifier(
            class: StrictChild::class,
            id: 1,
        );
    }

    public function testHydratorLazyProxyThrowsWhenBelongsToTargetRowIsMissing(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_children (id, owner_id, label) VALUES (2, 999, 'dangling')",
            native: true,
        );

        $child = $this->modelsManager->fetchByIdentifier(
            class: StrictChild::class,
            id: 2,
        );

        $this->expectException(ModelException::class);

        self::fail(
            'Expected ModelException, got owner name: ' . $child->owner->name,
        );
    }

    public function testHydratorLazyProxyThrowsWhenNonNullableHasOneTargetRowIsMissing(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_owners (id, name) VALUES (10, 'lonely')",
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
            class: StrictOwner::class,
            id: 10,
        );

        $this->expectException(ModelException::class);

        self::fail(
            'Expected ModelException, got profile handle: ' . $owner->profile->handle,
        );
    }

    public function testHydratorHasOneThroughThrowsWhenTargetRowIsMissing(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (100, 'Empty', 'EM')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (500, 'Dana', 'dana@example.test', 1, 0, 0.0, 100)",
            native: true,
        );

        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 100,
        );

        $this->expectException(ModelException::class);

        self::assertNotNull(
            $country->firstPost?->title,
        );
    }

    public function testLazyHasManyRelationCountBuilderAppliesAdditionalCriteria(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        $filtered = $user->posts->where(
            column: 'status',
            value: 'published',
        );

        self::assertSame(
            2,
            $filtered->totalCount,
        );
    }

    public function testLazyBelongsToManyLoaderHonoursOrderByAndLimit(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        $rows = \iterator_to_array(
            $user->roles
                ->orderBy('sortOrder')
                ->page(2)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $rows,
        );

        self::assertSame(
            'ADMIN',
            $rows[0]->key,
        );

        self::assertSame(
            'EDITOR',
            $rows[1]->key,
        );
    }

    public function testLazyBelongsToManyCountBuilderAppliesAdditionalCriteria(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        $filtered = $user->roles->where(
            column: 'roles.sortOrder',
            value: 3,
        );

        self::assertSame(
            1,
            $filtered->totalCount,
        );
    }

    public function testLazyHasManyThroughLoaderHonoursOrderByAndLimit(): void
    {
        $this->seedBaseGraph();

        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $country->posts,
        );

        $rows = \iterator_to_array(
            $country->posts
                ->orderBy('id')
                ->page(2)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $rows,
        );

        self::assertSame(
            10,
            $rows[0]->id,
        );
    }

    public function testLazyHasManyThroughCountBuilderAppliesAdditionalCriteria(): void
    {
        $this->seedBaseGraph();

        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $country->posts,
        );

        $filtered = $country->posts->where(
            column: 'status',
            value: 'draft',
        );

        self::assertSame(
            1,
            $filtered->totalCount,
        );
    }

    public function testHasOneThroughResolvesSecondLocalKeyOverrideOnThroughTable(): void
    {
        $this->seedRegionGraph();

        $region = $this->modelsManager->fetchByIdentifier(
            class: Region::class,
            id: 1,
        );

        self::assertInstanceOf(
            Warehouse::class,
            $region->primaryWarehouse,
        );

        self::assertSame(
            'Depot A',
            $region->primaryWarehouse->name,
        );
    }

    public function testHasManyThroughResolvesSecondLocalKeyOverrideOnThroughTable(): void
    {
        $this->seedRegionGraph();

        $region = $this->modelsManager->fetchByIdentifier(
            class: Region::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $region->warehouses,
        );

        self::assertSame(
            2,
            $region->warehouses->totalCount,
        );
    }

    public function testEagerLoadAcceptsNullConstraintClosureForBelongsTo(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
            with: [
                'country' => null,
            ],
        );

        self::assertInstanceOf(
            Country::class,
            $user->country,
        );

        self::assertSame(
            'Sweden',
            $user->country->name,
        );
    }

    public function testEagerLoadNestedPathCollectsBelongsToObjectChildren(): void
    {
        $this->seedBaseGraph();

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: User::class,
                with: [
                    'country.users' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $rows,
        );

        self::assertInstanceOf(
            Country::class,
            $rows[0]->country,
        );

        self::assertInstanceOf(
            Relation::class,
            $rows[0]->country->users,
        );

        self::assertSame(
            2,
            $rows[0]->country->users->totalCount,
        );
    }

    public function testEagerLoadNestedPathSkipsParentsWithNullBelongsToChild(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Orphan', 'orphan@example.test', 1, 0, 0.0, NULL)",
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: User::class,
                with: [
                    'country.users' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $rows,
        );

        self::assertNull(
            $rows[1]->country,
        );
    }

    public function testEagerLoadHasManyLeavesEmptyRelationForParentWithNullSource(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (10, 1, 'Alice one', '', 'published', 0, '0.00')",
            native: true,
        );

        $countries = \iterator_to_array(
            $this->modelsManager->findAll(
                class: Country::class,
                with: [
                    'users' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $countries,
        );

        self::assertInstanceOf(
            Relation::class,
            $countries[0]->users,
        );
    }

    public function testEagerLoadBelongsToAssignsNullOnParentsMissingSource(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Orphan', 'orphan@example.test', 1, 0, 0.0, NULL)",
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: User::class,
                with: [
                    'country' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertInstanceOf(
            Country::class,
            $rows[0]->country,
        );

        self::assertNull(
            $rows[1]->country,
        );
    }

    public function testEagerLoadBelongsToThrowsWhenNonNullableTargetIsMissing(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_children (id, owner_id, label) VALUES (5, 999, 'dangling')",
            native: true,
        );

        $this->expectException(ModelException::class);

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: StrictChild::class,
                with: [
                    'owner' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertNotEmpty($rows);
    }

    public function testEagerLoadHasOneThroughSkipsUnmatchedSecondKey(): void
    {
        $this->connection->query(
            sql: "INSERT INTO regions (id, name) VALUES (1, 'Nordic')",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO branches (id, region_id, warehouse_id) VALUES (10, 1, 999)',
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: Region::class,
                with: [
                    'primaryWarehouse' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertNull(
            $rows[0]->primaryWarehouse,
        );
    }

    public function testHydratorResolvesExplicitLocalKeyOnHasManyRelation(): void
    {
        $this->connection->query(
            sql: "INSERT INTO bulk_parents (id, name) VALUES (1, 'parent')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO bulk_children (id, parent_id, label) VALUES (10, 1, 'child')",
            native: true,
        );

        $parent = $this->modelsManager->fetchByIdentifier(
            class: BulkParent::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $parent->children,
        );

        self::assertSame(
            1,
            $parent->children->totalCount,
        );
    }

    public function testHydratorResolvesExplicitOwnerKeyOnBelongsToRelation(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_owners (id, name) VALUES (1, 'owner')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO strict_children (id, owner_id, label) VALUES (1, 1, 'child')",
            native: true,
        );

        $child = $this->modelsManager->fetchByIdentifier(
            class: StrictChild::class,
            id: 1,
        );

        self::assertSame(
            'owner',
            $child->owner->name,
        );
    }

    public function testEagerLoadConstraintClosureLimitSlicesPrefetchedResults(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
            with: [
                'posts' => static fn (Relation $relation): Relation => $relation->page(1),
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        $rows = \iterator_to_array(
            $user->posts->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );
    }

    public function testEagerLoadedHasManyRelationSupportsFurtherQueriesAfterHydration(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
            with: [
                'posts' => null,
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        $filtered = $user->posts->where(
            column: 'status',
            value: 'published',
        );

        self::assertSame(
            2,
            $filtered->totalCount,
        );

        $rows = \iterator_to_array(
            $filtered
                ->orderBy('id', 'DESC')
                ->page(1)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertSame(
            12,
            $rows[0]->id,
        );
    }

    public function testEagerLoadedBelongsToManyRelationSupportsFurtherQueriesAfterHydration(): void
    {
        $this->seedBaseGraph();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
            with: [
                'roles' => null,
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        $filtered = $user->roles->where(
            column: 'roles.sortOrder',
            value: 3,
        );

        self::assertSame(
            1,
            $filtered->totalCount,
        );

        $rows = \iterator_to_array(
            $filtered
                ->orderBy('sortOrder', 'DESC')
                ->page(1)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertSame(
            'VIEWER',
            $rows[0]->key,
        );
    }

    public function testEagerLoadedHasManyThroughRelationSupportsFurtherQueriesAfterHydration(): void
    {
        $this->seedBaseGraph();

        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 1,
            with: [
                'posts' => null,
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $country->posts,
        );

        $filtered = $country->posts->where(
            column: 'status',
            value: 'draft',
        );

        self::assertSame(
            1,
            $filtered->totalCount,
        );

        $rows = \iterator_to_array(
            $filtered
                ->orderBy('id', 'DESC')
                ->page(1)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertSame(
            11,
            $rows[0]->id,
        );
    }

    public function testNullableHasOneThroughSetsPropertyToNullWhenLocalKeyIsNull(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (1, NULL)',
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
            class: NullableThroughOwner::class,
            id: 1,
        );

        self::assertNull(
            $owner->primaryWarehouse,
        );
    }

    public function testHasManyThroughReturnsEmptyRelationWhenLocalKeyIsNull(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (2, NULL)',
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
            class: NullableThroughOwner::class,
            id: 2,
        );

        self::assertInstanceOf(
            Relation::class,
            $owner->warehouses,
        );

        self::assertSame(
            0,
            $owner->warehouses->totalCount,
        );
    }

    public function testNonNullableHasOneThroughThrowsWhenLocalKeyIsNull(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO strict_through_owners (id, nullable_ref_id) VALUES (3, NULL)',
            native: true,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByIdentifier(
            class: StrictThroughOwner::class,
            id: 3,
        );
    }

    public function testEagerLoadRejectsUnknownRelationName(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
            with: [
                'bogusRelation' => null,
            ],
        );
    }

    public function testEagerLoadBelongsToThrowsWhenNonNullableForeignKeyIsNull(): void
    {
        $this->connection->query(
            sql: "INSERT INTO strict_children (id, owner_id, label) VALUES (6, NULL, 'orphan')",
            native: true,
        );

        $this->expectException(ModelException::class);

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: StrictChild::class,
                with: [
                    'owner' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertNotEmpty($rows);
    }

    public function testEagerLoadHasOneThroughSkipsParentsWithNullLocalKey(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (4, NULL)',
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: NullableThroughOwner::class,
                with: [
                    'primaryWarehouse' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertNull(
            $rows[0]->primaryWarehouse,
        );
    }

    public function testEagerLoadHasManyThroughSkipsParentsWithNullLocalKey(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (5, NULL)',
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: NullableThroughOwner::class,
                with: [
                    'warehouses' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertInstanceOf(
            Relation::class,
            $rows[0]->warehouses,
        );

        self::assertSame(
            0,
            $rows[0]->warehouses->totalCount,
        );
    }

    public function testEagerLoadHasOneSkipsParentsWithNullLocalKey(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (6, NULL)',
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: NullableThroughOwner::class,
                with: [
                    'firstBranch' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertNull(
            $rows[0]->firstBranch,
        );
    }

    public function testEagerLoadHasManySkipsParentsWithNullLocalKey(): void
    {
        $this->connection->query(
            sql: 'INSERT INTO nullable_through_owners (id, nullable_ref_id) VALUES (7, NULL)',
            native: true,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: NullableThroughOwner::class,
                with: [
                    'branches' => null,
                ],
            ),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );

        self::assertInstanceOf(
            Relation::class,
            $rows[0]->branches,
        );

        self::assertSame(
            0,
            $rows[0]->branches->totalCount,
        );
    }
}
