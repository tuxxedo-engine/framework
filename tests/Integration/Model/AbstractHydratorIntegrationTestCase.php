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

abstract class AbstractHydratorIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAllFixtureTables();
        $this->createStrictOwnersTable();
        $this->createStrictProfilesTable();
        $this->createStrictChildrenTable();
        $this->createRegionsTable();
        $this->createBranchesTable();
        $this->createWarehousesTable();
        $this->createBulkParentsTable();
        $this->createBulkChildrenTable();
        $this->createNullableThroughOwnersTable();
        $this->createStrictThroughOwnersTable();
    }

    private function seedRegionGraph(): void
    {
        $this->seedRegion(id: 1, name: 'Nordic');
        $this->seedWarehouse(id: 100, name: 'Depot A');
        $this->seedWarehouse(id: 101, name: 'Depot B');
        $this->seedBranch(id: 10, regionId: 1, warehouseId: 100);
        $this->seedBranch(id: 11, regionId: 1, warehouseId: 101);
    }

    private function seedRegion(int $id, string $name): void
    {
        $this->connection->insert(table: 'regions')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedWarehouse(int $id, string $name): void
    {
        $this->connection->insert(table: 'warehouses')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedBranch(int $id, int $regionId, int $warehouseId): void
    {
        $this->connection->insert(table: 'branches')
            ->set(column: 'id', value: $id)
            ->set(column: 'region_id', value: $regionId)
            ->set(column: 'warehouse_id', value: $warehouseId)
            ->execute();
    }

    private function seedBaseGraph(): void
    {
        $this->seedCountry(id: 1, name: 'Sweden', code: 'SE');
        $this->seedUserGraph(id: 1, name: 'Alice', countryId: 1);
        $this->seedUserGraph(id: 2, name: 'Bob', countryId: 1);
        $this->seedPostGraph(id: 10, userId: 1, title: 'Alice one', status: 'published');
        $this->seedPostGraph(id: 11, userId: 1, title: 'Alice two', status: 'draft');
        $this->seedPostGraph(id: 12, userId: 1, title: 'Alice three', status: 'published');
        $this->seedPostGraph(id: 13, userId: 2, title: 'Bob one', status: 'published');
        $this->seedRole(id: 200, key: 'ADMIN', label: 'Administrator', sortOrder: 1);
        $this->seedRole(id: 201, key: 'EDITOR', label: 'Editor', sortOrder: 2);
        $this->seedRole(id: 202, key: 'VIEWER', label: 'Viewer', sortOrder: 3);
        $this->seedUserRole(userId: 1, roleId: 200);
        $this->seedUserRole(userId: 1, roleId: 201);
        $this->seedUserRole(userId: 1, roleId: 202);
    }

    private function seedCountry(int $id, string $name, string $code): void
    {
        $this->connection->insert(table: 'countries')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'code', value: $code)
            ->execute();
    }

    private function seedUserGraph(int $id, string $name, ?int $countryId): void
    {
        $this->connection->insert(table: 'users')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->set(column: 'country_id', value: $countryId)
            ->execute();
    }

    private function seedPostGraph(int $id, int $userId, string $title, string $status): void
    {
        $this->connection->insert(table: 'posts')
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->set(column: 'status', value: $status)
            ->set(column: 'viewCount', value: 0)
            ->set(column: 'rating', value: '0.00')
            ->execute();
    }

    private function seedRole(int $id, string $key, string $label, int $sortOrder): void
    {
        $this->connection->insert(table: 'roles')
            ->set(column: 'id', value: $id)
            ->set(column: 'key', value: $key)
            ->set(column: 'label', value: $label)
            ->set(column: 'sortOrder', value: $sortOrder)
            ->execute();
    }

    private function seedUserRole(int $userId, int $roleId): void
    {
        $this->connection->insert(table: 'user_role')
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'role_id', value: $roleId)
            ->execute();
    }

    private function seedStrictOwner(int $id, string $name): void
    {
        $this->connection->insert(table: 'strict_owners')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedStrictChild(int $id, ?int $ownerId, string $label): void
    {
        $this->connection->insert(table: 'strict_children')
            ->set(column: 'id', value: $id)
            ->set(column: 'owner_id', value: $ownerId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedBulkParent(int $id, string $name): void
    {
        $this->connection->insert(table: 'bulk_parents')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedBulkChild(int $id, int $parentId, string $label): void
    {
        $this->connection->insert(table: 'bulk_children')
            ->set(column: 'id', value: $id)
            ->set(column: 'parent_id', value: $parentId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedNullableThroughOwner(int $id, ?int $nullableRefId): void
    {
        $this->connection->insert(table: 'nullable_through_owners')
            ->set(column: 'id', value: $id)
            ->set(column: 'nullable_ref_id', value: $nullableRefId)
            ->execute();
    }

    private function seedStrictThroughOwner(int $id, ?int $nullableRefId): void
    {
        $this->connection->insert(table: 'strict_through_owners')
            ->set(column: 'id', value: $id)
            ->set(column: 'nullable_ref_id', value: $nullableRefId)
            ->execute();
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
        $this->seedStrictChild(id: 1, ownerId: null, label: 'orphan');

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchById(
            class: StrictChild::class,
            id: 1,
        );
    }

    public function testHydratorLazyProxyThrowsWhenBelongsToTargetRowIsMissing(): void
    {
        $this->seedStrictChild(id: 2, ownerId: 999, label: 'dangling');

        $child = $this->modelsManager->fetchById(
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
        $this->seedStrictOwner(id: 10, name: 'lonely');

        $owner = $this->modelsManager->fetchById(
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
        $this->seedCountry(id: 100, name: 'Empty', code: 'EM');

        $this->seedUserGraph(id: 500, name: 'Dana', countryId: 100);

        $country = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $country = $this->modelsManager->fetchById(
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

        $country = $this->modelsManager->fetchById(
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

        $region = $this->modelsManager->fetchById(
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

        $region = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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
        $this->seedCountry(id: 1, name: 'Sweden', code: 'SE');

        $this->seedUserGraph(id: 1, name: 'Alice', countryId: 1);

        $this->seedUserGraph(id: 2, name: 'Orphan', countryId: null);

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
        $this->seedCountry(id: 1, name: 'Sweden', code: 'SE');

        $this->seedUserGraph(id: 1, name: 'Alice', countryId: 1);

        $this->seedPostGraph(id: 10, userId: 1, title: 'Alice one', status: 'published');

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
        $this->seedCountry(id: 1, name: 'Sweden', code: 'SE');

        $this->seedUserGraph(id: 1, name: 'Alice', countryId: 1);

        $this->seedUserGraph(id: 2, name: 'Orphan', countryId: null);

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
        $this->seedStrictChild(id: 5, ownerId: 999, label: 'dangling');

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
        $this->seedRegion(id: 1, name: 'Nordic');

        $this->seedBranch(id: 10, regionId: 1, warehouseId: 999);

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
        $this->seedBulkParent(id: 1, name: 'parent');

        $this->seedBulkChild(id: 10, parentId: 1, label: 'child');

        $parent = $this->modelsManager->fetchById(
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
        $this->seedStrictOwner(id: 1, name: 'owner');

        $this->seedStrictChild(id: 1, ownerId: 1, label: 'child');

        $child = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $user = $this->modelsManager->fetchById(
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

        $country = $this->modelsManager->fetchById(
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
        $this->seedNullableThroughOwner(id: 1, nullableRefId: null);

        $owner = $this->modelsManager->fetchById(
            class: NullableThroughOwner::class,
            id: 1,
        );

        self::assertNull(
            $owner->primaryWarehouse,
        );
    }

    public function testHasManyThroughReturnsEmptyRelationWhenLocalKeyIsNull(): void
    {
        $this->seedNullableThroughOwner(id: 2, nullableRefId: null);

        $owner = $this->modelsManager->fetchById(
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
        $this->seedStrictThroughOwner(id: 3, nullableRefId: null);

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchById(
            class: StrictThroughOwner::class,
            id: 3,
        );
    }

    public function testEagerLoadRejectsUnknownRelationName(): void
    {
        $this->seedCountry(id: 1, name: 'Sweden', code: 'SE');

        $this->seedUserGraph(id: 1, name: 'Alice', countryId: 1);

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchById(
            class: User::class,
            id: 1,
            with: [
                'bogusRelation' => null,
            ],
        );
    }

    public function testEagerLoadBelongsToThrowsWhenNonNullableForeignKeyIsNull(): void
    {
        $this->seedStrictChild(id: 6, ownerId: null, label: 'orphan');

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
        $this->seedNullableThroughOwner(id: 4, nullableRefId: null);

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
        $this->seedNullableThroughOwner(id: 5, nullableRefId: null);

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
        $this->seedNullableThroughOwner(id: 6, nullableRefId: null);

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
        $this->seedNullableThroughOwner(id: 7, nullableRefId: null);

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
