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
use Fixture\Model\CascadeBelongsToChild;
use Fixture\Model\CascadeGroup;
use Fixture\Model\Country;
use Fixture\Model\OrphanChild;
use Fixture\Model\OrphanParent;
use Fixture\Model\Post;
use Fixture\Model\ReadonlyRecord;
use Fixture\Model\Setting;
use Fixture\Model\StrictOwner;
use Fixture\Model\StrictProfile;
use Fixture\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Query;
use Tuxxedo\Model\Relation;

abstract class AbstractModelsManagerIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAllFixtureTables();
        $this->createReadonlyRecordsTable();
        $this->createSettingsTable();
        $this->createBulkParentsTable();
        $this->createBulkChildrenTable();
        $this->createOrphanParentsTable();
        $this->createOrphanChildrenTable();
        $this->createCascadeBelongsToParentsTable();
        $this->createCascadeBelongsToChildrenTable();
        $this->createCascadeGroupsTable();
        $this->createCascadeChildrenTable();
        $this->createCascadeHasOneChildrenTable();
        $this->createCascadeHasOneRestrictChildrenTable();
        $this->createCascadeTagsTable();
        $this->createCascadeGroupTagPivot();
        $this->createStrictOwnersTable();
        $this->createStrictProfilesTable();
    }

    private function seedCountry(
        int $id,
        string $name = 'Sweden',
    ): void {
        $this->connection->insert(
            table: 'countries',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'code', value: 'SE')
            ->execute();
    }

    private function seedUser(
        int $id,
        string $name = 'Alice',
        ?int $countryId = 1,
    ): void {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: $name . '@example.test')
            ->set(column: 'country_id', value: $countryId)
            ->execute();
    }

    private function seedPost(
        int $id,
        int $userId,
        string $title = 'Post',
    ): void {
        $this->connection->insert(
            table: 'posts',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->execute();
    }

    private function seedStrictOwner(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'strict_owners',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedStrictProfile(
        int $id,
        int $ownerId,
        string $handle,
    ): void {
        $this->connection->insert(
            table: 'strict_profiles',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'owner_id', value: $ownerId)
            ->set(column: 'handle', value: $handle)
            ->execute();
    }

    private function seedCascadeGroup(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'cascade_groups',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedCascadeChild(
        int $id,
        int $autoGroupId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'cascade_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'auto_group_id', value: $autoGroupId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCascadeHasOneChild(
        int $id,
        int $groupId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'cascade_hasone_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'group_id', value: $groupId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCascadeTag(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'cascade_tags',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedCascadeGroupTag(
        int $groupId,
        int $tagId,
    ): void {
        $this->connection->insert(
            table: 'cascade_group_tag',
        )
            ->set(column: 'group_id', value: $groupId)
            ->set(column: 'tag_id', value: $tagId)
            ->execute();
    }

    private function seedBulkChild(
        int $parentId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'bulk_children',
        )
            ->set(column: 'parent_id', value: $parentId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedOrphanChild(
        int $id,
        int $parentId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'orphan_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'parent_id', value: $parentId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCascadeBtParent(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'cascade_bt_parents',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedCascadeBtChild(
        int $id,
        ?int $parentId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'cascade_bt_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'parent_id', value: $parentId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedProfile(
        int $userId,
        string $bio,
    ): void {
        $this->connection->insert(
            table: 'profiles',
        )
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'bio', value: $bio)
            ->execute();
    }

    private function seedSetting(
        string $scope,
        string $name,
        string $value,
    ): void {
        $this->connection->insert(
            table: 'settings',
        )
            ->set(column: 'scope', value: $scope)
            ->set(column: 'name', value: $name)
            ->set(column: 'value', value: $value)
            ->execute();
    }

    private function countRowsWhere(
        string $table,
        string $column,
        int|string $value,
    ): int {
        return $this->connection->count(
            table: $table,
        )
            ->where(column: $column, value: $value)
            ->count();
    }

    public function testReadonlyModelInsertUsesCloneWithChangesForAutoIncrementKey(): void
    {
        $record = new ReadonlyRecord(
            name: 'immutable',
        );

        $saved = $this->modelsManager->save($record);

        self::assertNotSame(
            $record,
            $saved,
        );

        self::assertNotNull(
            $saved->id,
        );

        self::assertSame(
            'immutable',
            $saved->name,
        );
    }

    public function testFindByIdentifierThrowsOnCompositeKeyModel(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->findById(
            class: Setting::class,
            id: 1,
        );
    }

    public function testFindByCompositeKeyThrowsOnPrimaryKeyModel(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->findByCompositeKey(
            class: User::class,
            keys: [
                'id' => 1,
            ],
        );
    }

    public function testFindByIdentifierRunsCallerProvidedCriteria(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 100,
            name: 'Alice',
        );

        $result = $this->modelsManager->findById(
            class: User::class,
            id: 100,
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'Alice',
                );
            },
        );

        self::assertInstanceOf(
            User::class,
            $result,
        );
    }

    public function testFindByCompositeKeyRunsCallerProvidedCriteria(): void
    {
        $this->seedSetting(
            scope: 'ui',
            name: 'lang',
            value: 'en',
        );

        $result = $this->modelsManager->findByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'ui',
                'name' => 'lang',
            ],
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'value',
                    value: 'en',
                );
            },
        );

        self::assertInstanceOf(
            Setting::class,
            $result,
        );
    }

    public function testExistsByIdentifierThrowsOnCompositeKeyModel(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->existsById(
            class: Setting::class,
            id: 1,
        );
    }

    public function testExistsByIdentifierRunsCallerProvidedCriteria(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 200,
            name: 'Bob',
        );

        $exists = $this->modelsManager->existsById(
            class: User::class,
            id: 200,
            criteria: static function (ExistsStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'Bob',
                );
            },
        );

        self::assertTrue(
            $exists,
        );
    }

    public function testFindAllChunkedEagerLoopFlushesMultipleChunksAndTail(): void
    {
        $this->seedCountry(
            id: 1,
        );

        for ($i = 1; $i <= 5; $i++) {
            $this->seedUser(
                id: $i,
                name: 'User' . $i,
            );
        }

        $rows = \iterator_to_array(
            $this->modelsManager->findAll(
                class: User::class,
                chunkSize: 2,
            ),
        );

        self::assertCount(
            5,
            $rows,
        );
    }

    public function testRefreshReturnsFreshCopyIncludingSoftDeletedRow(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 10,
            name: 'Original',
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 10,
        );

        $this->connection->update(
            table: 'users',
        )
            ->set(column: 'name', value: 'External')
            ->where(column: 'id', value: 10)
            ->execute();

        $refreshed = $this->modelsManager->refresh($user);

        self::assertSame(
            'External',
            $refreshed->name,
        );
    }

    public function testRefreshThrowsForModelWithoutPrimaryKey(): void
    {
        $setting = new Setting();
        $setting->scope = 'x';
        $setting->name = 'y';

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->refresh($setting);
    }

    public function testRefreshThrowsWhenIdentifierValueIsNotScalar(): void
    {
        $user = new User();

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->refresh($user);
    }

    public function testRefreshThrowsWhenRowNoLongerExists(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 20,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 20,
        );

        $this->connection->delete(
            table: 'users',
        )
            ->where(column: 'id', value: 20)
            ->execute();

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->refresh($user);
    }

    public function testQueryProducesQueryInstanceAndCountBuilderExecutes(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 30,
            name: 'C',
        );

        $this->seedUser(
            id: 31,
            name: 'D',
        );

        $query = $this->modelsManager->query(User::class);

        self::assertInstanceOf(
            Query::class,
            $query,
        );

        self::assertSame(
            2,
            $query->totalCount,
        );
    }

    public function testQueryCountBuilderAppliesCriteria(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 40,
            name: 'One',
        );

        $this->seedUser(
            id: 41,
            name: 'Two',
        );

        $filtered = $this->modelsManager->query(User::class)
            ->where(
                column: 'name',
                value: 'One',
            );

        self::assertSame(
            1,
            $filtered->totalCount,
        );
    }

    public function testIsRelationLoadedReturnsFalseForUninitializedLazyRelation(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 50,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 50,
        );

        self::assertFalse(
            $this->modelsManager->isRelationLoaded(
                model: $user,
                property: 'country',
            ),
        );
    }

    public function testIsRelationLoadedReturnsTrueOnceLazyRelationIsMaterialized(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 60,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 60,
        );

        self::assertSame(
            'Sweden',
            $user->country?->name,
        );

        self::assertTrue(
            $this->modelsManager->isRelationLoaded(
                model: $user,
                property: 'country',
            ),
        );
    }

    public function testIsRelationLoadedReturnsTrueForNullBelongsToRelation(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 70,
            name: 'orphan',
            countryId: null,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 70,
        );

        self::assertTrue(
            $this->modelsManager->isRelationLoaded(
                model: $user,
                property: 'country',
            ),
        );
    }

    public function testRelationReturnsMaterializedRelationForLazyProxy(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 80,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 80,
        );

        $country = $this->modelsManager->relation(
            model: $user,
            property: 'country',
        );

        self::assertInstanceOf(
            Country::class,
            $country,
        );
    }

    public function testRelationReturnsNullWhenBelongsToValueIsNull(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 81,
            name: 'null-country',
            countryId: null,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 81,
        );

        self::assertNull(
            $this->modelsManager->relation(
                model: $user,
                property: 'country',
            ),
        );
    }

    public function testRelationThrowsForUnknownRelationProperty(): void
    {
        $user = new User();

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->relation(
            model: $user,
            property: 'unknownRelation',
        );
    }

    public function testTrackAsExistingRecordsSnapshotSoUpdatePathIsUsed(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $user = new User();
        $user->id = 90;
        $user->name = 'seeded';
        $user->email = 'seed@example.test';
        $user->countryId = 1;

        $this->seedUser(
            id: 90,
            name: 'seeded',
        );

        $this->modelsManager->trackAsExisting($user);

        $user->name = 'renamed';

        (void) $this->modelsManager->save($user);

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->where(column: 'id', value: 90)
            ->limit(1)
            ->execute();

        $row = $result->fetchAssoc();

        self::assertSame(
            'renamed',
            $row['name'] ?? null,
        );
    }

    public function testHasManyCascadeForceDeleteBypassesSoftDelete(): void
    {
        $this->seedCascadeGroup(
            id: 200,
            name: 'group',
        );

        $this->seedCascadeChild(
            id: 300,
            autoGroupId: 200,
            label: 'child',
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 200,
        );

        $deleted = $this->modelsManager->forceDelete($group);

        self::assertTrue(
            $deleted,
        );

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'cascade_children',
                column: 'auto_group_id',
                value: 200,
            ),
        );
    }

    public function testHasOneCascadeForceDelete(): void
    {
        $this->seedCascadeGroup(
            id: 210,
            name: 'group',
        );

        $this->seedCascadeHasOneChild(
            id: 310,
            groupId: 210,
            label: 'child',
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 210,
        );

        (void) $this->modelsManager->forceDelete($group);

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'cascade_hasone_children',
                column: 'group_id',
                value: 210,
            ),
        );
    }

    public function testBulkDeleteHasManyIssuesSingleWhereDelete(): void
    {
        $parent = new BulkParent();
        $parent->name = 'p';

        $saved = $this->modelsManager->save($parent);

        self::assertNotNull(
            $saved->id,
        );

        $this->seedBulkChild(
            parentId: $saved->id,
            label: 'a',
        );

        $this->seedBulkChild(
            parentId: $saved->id,
            label: 'b',
        );

        (void) $this->modelsManager->delete($saved);

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'bulk_children',
                column: 'parent_id',
                value: $saved->id,
            ),
        );
    }

    public function testRemoveOrphanDeletesPendingRemovesDuringCascadeSave(): void
    {
        $parent = new OrphanParent();
        $parent->name = 'orphan-parent';

        $saved = $this->modelsManager->save($parent);

        self::assertNotNull(
            $saved->id,
        );

        $this->seedOrphanChild(
            id: 500,
            parentId: $saved->id,
            label: 'to-remove',
        );

        $fetched = $this->modelsManager->fetchById(
            class: OrphanParent::class,
            id: $saved->id,
        );

        $children = $fetched->children;

        self::assertInstanceOf(
            Relation::class,
            $children,
        );

        $child = $children->first();

        self::assertInstanceOf(
            OrphanChild::class,
            $child,
        );

        $children->remove($child);

        (void) $this->modelsManager->save($fetched);

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'orphan_children',
                column: 'id',
                value: 500,
            ),
        );
    }

    public function testBelongsToCascadeOnDeleteRemovesParent(): void
    {
        $this->seedCascadeBtParent(
            id: 700,
            name: 'parent',
        );

        $this->seedCascadeBtChild(
            id: 800,
            parentId: 700,
            label: 'child',
        );

        $child = $this->modelsManager->fetchById(
            class: CascadeBelongsToChild::class,
            id: 800,
        );

        (void) $this->modelsManager->delete($child);

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'cascade_bt_parents',
                column: 'id',
                value: 700,
            ),
        );
    }

    public function testHasOneThroughRelationRejectsPendingChangesOnSave(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 900,
        );

        $this->seedPost(
            id: 901,
            userId: 900,
        );

        $country = $this->modelsManager->fetchById(
            class: Country::class,
            id: 1,
        );

        $firstPost = $country->firstPost;

        self::assertInstanceOf(
            Post::class,
            $firstPost,
        );

        $country->name = 'Renamed';

        (void) $this->modelsManager->save($country);

        self::assertSame(
            'Renamed',
            $country->name,
        );
    }

    public function testHasManyThroughRejectsPendingAddOnSave(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 910,
        );

        $country = $this->modelsManager->fetchById(
            class: Country::class,
            id: 1,
        );

        $postsRelation = $country->posts;

        self::assertInstanceOf(
            Relation::class,
            $postsRelation,
        );

        $postsRelation->add(new Post());

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->save($country);
    }

    public function testFindByIdentifierIncludesEagerLoadingForSpecifiedRelations(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 920,
        );

        $this->seedPost(
            id: 921,
            userId: 920,
        );

        $this->seedPost(
            id: 922,
            userId: 920,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 920,
            with: [
                'posts' => static fn (Relation $r): Relation => $r,
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        self::assertSame(
            2,
            $user->posts->totalCount,
        );
    }

    public function testCountAppliesCriteria(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 930,
            name: 'One',
        );

        $this->seedUser(
            id: 931,
            name: 'Two',
        );

        $result = $this->modelsManager->count(
            class: User::class,
            criteria: static function (CountStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'One',
                );
            },
        );

        self::assertSame(
            1,
            $result,
        );
    }

    public function testQueryFetchAllRunsEagerLoaderClosure(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 940,
        );

        $this->seedPost(
            id: 941,
            userId: 940,
        );

        $rows = \iterator_to_array(
            $this->modelsManager->query(User::class)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            1,
            $rows,
        );
    }

    public function testMergeAutoEagerWithSkipsNonNullableHasOne(): void
    {
        $this->seedStrictOwner(
            id: 1,
            name: 'anna',
        );

        $this->seedStrictProfile(
            id: 1,
            ownerId: 1,
            handle: 'anna-handle',
        );

        $owner = $this->modelsManager->fetchById(
            class: StrictOwner::class,
            id: 1,
        );

        self::assertSame(
            'anna',
            $owner->name,
        );

        self::assertInstanceOf(
            StrictProfile::class,
            $this->modelsManager->relation(
                model: $owner,
                property: 'profile',
            ),
        );
    }

    public function testCascadeSaveSkipsHasOneRelationLeftAsUninitializedLazy(): void
    {
        $this->seedStrictOwner(
            id: 500,
            name: 'lazy',
        );

        $this->seedStrictProfile(
            id: 600,
            ownerId: 500,
            handle: 'lazy-handle',
        );

        $owner = $this->modelsManager->fetchById(
            class: StrictOwner::class,
            id: 500,
        );

        $owner->name = 'renamed';

        (void) $this->modelsManager->save($owner);

        self::assertSame(
            'renamed',
            $owner->name,
        );
    }

    public function testCascadeSaveForceMaterializesUninitializedLazyHasOne(): void
    {
        $this->seedStrictOwner(
            id: 510,
            name: 'force',
        );

        $this->seedStrictProfile(
            id: 610,
            ownerId: 510,
            handle: 'force-handle',
        );

        $owner = $this->modelsManager->fetchById(
            class: StrictOwner::class,
            id: 510,
        );

        (void) $this->modelsManager->save(
            model: $owner,
            forceMaterialize: true,
        );

        self::assertNotNull(
            $owner->id,
        );
    }

    public function testCascadeSaveForceMaterializesBelongsToManyRelationForeach(): void
    {
        $this->seedCascadeGroup(
            id: 1200,
            name: 'g',
        );

        $this->seedCascadeTag(
            id: 1300,
            name: 'red',
        );

        $this->seedCascadeGroupTag(
            groupId: 1200,
            tagId: 1300,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 1200,
        );

        (void) $this->modelsManager->save(
            model: $group,
            forceMaterialize: true,
        );

        self::assertSame(
            1,
            $this->connection->count(
                table: 'cascade_group_tag',
            )
                ->where(column: 'group_id', value: 1200)
                ->where(column: 'tag_id', value: 1300)
                ->count(),
        );
    }

    public function testUpdateDehydratesNullableColumnValueToNull(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 1500,
            name: 'has-country',
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 1500,
        );

        $user->countryId = null;

        (void) $this->modelsManager->save($user);

        $refetched = $this->modelsManager->fetchById(
            class: User::class,
            id: 1500,
        );

        self::assertNull(
            $refetched->countryId,
        );
    }

    public function testCascadeDeleteSkipsBelongsToWhenValueIsNull(): void
    {
        $this->seedCascadeBtChild(
            id: 900,
            parentId: null,
            label: 'orphan',
        );

        $child = $this->modelsManager->fetchById(
            class: CascadeBelongsToChild::class,
            id: 900,
        );

        $deleted = $this->modelsManager->delete($child);

        self::assertTrue(
            $deleted,
        );
    }

    public function testCascadeForceDeleteRoutesBelongsToParentThroughForceDelete(): void
    {
        $this->seedCascadeBtParent(
            id: 901,
            name: 'force-parent',
        );

        $this->seedCascadeBtChild(
            id: 902,
            parentId: 901,
            label: 'force-child',
        );

        $child = $this->modelsManager->fetchById(
            class: CascadeBelongsToChild::class,
            id: 902,
        );

        (void) $this->modelsManager->forceDelete($child);

        self::assertSame(
            0,
            $this->countRowsWhere(
                table: 'cascade_bt_parents',
                column: 'id',
                value: 901,
            ),
        );
    }

    public function testMergeAutoEagerWithSkipsRelationAlreadyProvidedInExplicitWith(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 1600,
        );

        $this->seedProfile(
            userId: 1600,
            bio: 'x',
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 1600,
            with: [
                'profile' => static fn (Relation $r): Relation => $r,
            ],
        );

        self::assertNotNull(
            $user->profile,
        );
    }

    public function testIsRelationLoadedReturnsFalseWhenPropertyIsUninitialized(): void
    {
        $owner = new StrictOwner();

        self::assertFalse(
            $this->modelsManager->isRelationLoaded(
                model: $owner,
                property: 'profile',
            ),
        );
    }

    public function testRelationReturnsAlreadyInitializedObjectWithoutReMaterializing(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 1700,
        );

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 1700,
        );

        (void) $this->modelsManager->relation(
            model: $user,
            property: 'country',
        );

        $second = $this->modelsManager->relation(
            model: $user,
            property: 'country',
        );

        self::assertInstanceOf(
            Country::class,
            $second,
        );
    }

    public function testQueryHonoursOrderByAndLimit(): void
    {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 950,
            name: 'C',
        );

        $this->seedUser(
            id: 951,
            name: 'A',
        );

        $this->seedUser(
            id: 952,
            name: 'B',
        );

        $rows = \iterator_to_array(
            $this->modelsManager->query(User::class)
                ->orderBy('name')
                ->page(2)
                ->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $rows,
        );

        self::assertSame(
            'A',
            $rows[0]->name,
        );
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function malformedEagerLoadPathDataProvider(): \Generator
    {
        yield [
            '',
        ];

        yield [
            '.posts',
        ];

        yield [
            'posts.',
        ];

        yield [
            'posts..comments',
        ];
    }

    #[DataProvider('malformedEagerLoadPathDataProvider')]
    public function testFetchByIdentifierRejectsMalformedEagerLoadPath(
        string $path,
    ): void {
        $this->seedCountry(
            id: 1,
        );

        $this->seedUser(
            id: 2000,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchById(
            class: User::class,
            id: 2000,
            with: [
                $path => static fn (Relation $r): Relation => $r,
            ],
        );
    }
}
