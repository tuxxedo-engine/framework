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

class ModelsManagerIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAllFixtureTables();

        $this->connection->query(
            sql: 'CREATE TABLE readonly_records (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE settings (' .
                'scope TEXT NOT NULL, ' .
                'name TEXT NOT NULL, ' .
                'value TEXT NOT NULL DEFAULT \'\', ' .
                'PRIMARY KEY (scope, name)' .
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
            sql: 'CREATE TABLE orphan_parents (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE orphan_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'parent_id INTEGER NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_bt_parents (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_bt_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'parent_id INTEGER NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_groups (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'auto_group_id INTEGER NULL, ' .
                'restrict_group_id INTEGER NULL, ' .
                'nullable_group_id INTEGER NULL, ' .
                'noaction_group_id INTEGER NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_hasone_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'group_id INTEGER NOT NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_hasone_restrict_children (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'group_id INTEGER NOT NULL, ' .
                'label TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_tags (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );

        $this->connection->query(
            sql: 'CREATE TABLE cascade_group_tag (' .
                'group_id INTEGER NOT NULL, ' .
                'tag_id INTEGER NOT NULL, ' .
                'PRIMARY KEY (group_id, tag_id)' .
                ')',
            native: true,
        );

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
    }

    private function seedCountry(int $id, string $name = 'Sweden'): void
    {
        $this->connection->query(
            sql: 'INSERT INTO countries (id, name, code) VALUES (' . $id . ", '" . $name . "', 'SE')",
            native: true,
        );
    }

    private function seedUser(int $id, string $name = 'Alice', int $countryId = 1): void
    {
        $this->connection->query(
            sql: 'INSERT INTO users (id, name, email, country_id) VALUES (' . $id . ", '" . $name . "', '" . $name . "@example.test', " . $countryId . ')',
            native: true,
        );
    }

    private function seedPost(int $id, int $userId, string $title = 'Post'): void
    {
        $this->connection->query(
            sql: 'INSERT INTO posts (id, user_id, title) VALUES (' . $id . ', ' . $userId . ", '" . $title . "')",
            native: true,
        );
    }

    private function countRowsIn(string $table, string $where = '1=1'): mixed
    {
        $row = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM ' . $table . ' WHERE ' . $where,
            native: true,
        )->fetchAssoc();

        return $row['c'] ?? 0;
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

        (void) $this->modelsManager->findByIdentifier(
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
        $this->seedCountry(id: 1);
        $this->seedUser(id: 100, name: 'Alice');

        $result = $this->modelsManager->findByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO settings (scope, name, value) VALUES ('ui', 'lang', 'en')",
            native: true,
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

        (void) $this->modelsManager->existsByIdentifier(
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

        $exists = $this->modelsManager->existsByIdentifier(
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

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 10,
        );

        $this->connection->query(
            sql: "UPDATE users SET name = 'External' WHERE id = 10",
            native: true,
        );

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

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 20,
        );

        $this->connection->query(
            sql: 'DELETE FROM users WHERE id = 20',
            native: true,
        );

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

        $query = $this->modelsManager->query(
            class: User::class,
        );

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

        $user = $this->modelsManager->fetchByIdentifier(
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

        $user = $this->modelsManager->fetchByIdentifier(
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

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, country_id) VALUES (70, 'orphan', 'orphan@example.test', NULL)",
            native: true,
        );

        $user = $this->modelsManager->fetchByIdentifier(
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

        $user = $this->modelsManager->fetchByIdentifier(
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

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, country_id) VALUES (81, 'null-country', 'null@example.test', NULL)",
            native: true,
        );

        $user = $this->modelsManager->fetchByIdentifier(
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

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, country_id) VALUES (90, 'seeded', 'seed@example.test', 1)",
            native: true,
        );

        $this->modelsManager->trackAsExisting($user);

        $user->name = 'renamed';

        (void) $this->modelsManager->save($user);

        $row = $this->connection->query(
            sql: 'SELECT name FROM users WHERE id = 90',
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'renamed',
            $row['name'] ?? null,
        );
    }

    public function testHasManyCascadeForceDeleteBypassesSoftDelete(): void
    {
        $this->connection->query(
            sql: "INSERT INTO cascade_groups (id, name) VALUES (200, 'group')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, auto_group_id, label) VALUES (300, 200, 'child')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 200,
        );

        $deleted = $this->modelsManager->forceDelete($group);

        self::assertTrue(
            $deleted,
        );

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_children',
                where: 'auto_group_id = 200',
            ),
        );
    }

    public function testHasOneCascadeForceDelete(): void
    {
        $this->connection->query(
            sql: "INSERT INTO cascade_groups (id, name) VALUES (210, 'group')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_hasone_children (id, group_id, label) VALUES (310, 210, 'child')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 210,
        );

        (void) $this->modelsManager->forceDelete($group);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_hasone_children',
                where: 'group_id = 210',
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

        $this->connection->query(
            sql: "INSERT INTO bulk_children (parent_id, label) VALUES (" . $saved->id . ", 'a')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO bulk_children (parent_id, label) VALUES (" . $saved->id . ", 'b')",
            native: true,
        );

        (void) $this->modelsManager->delete($saved);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'bulk_children',
                where: 'parent_id = ' . $saved->id,
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

        $this->connection->query(
            sql: "INSERT INTO orphan_children (id, parent_id, label) VALUES (500, " . $saved->id . ", 'to-remove')",
            native: true,
        );

        $fetched = $this->modelsManager->fetchByIdentifier(
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

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'orphan_children',
                where: 'id = 500',
            ),
        );
    }

    public function testBelongsToCascadeOnDeleteRemovesParent(): void
    {
        $this->connection->query(
            sql: "INSERT INTO cascade_bt_parents (id, name) VALUES (700, 'parent')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_bt_children (id, parent_id, label) VALUES (800, 700, 'child')",
            native: true,
        );

        $child = $this->modelsManager->fetchByIdentifier(
            class: CascadeBelongsToChild::class,
            id: 800,
        );

        (void) $this->modelsManager->delete($child);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_bt_parents',
                where: 'id = 700',
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

        $country = $this->modelsManager->fetchByIdentifier(
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

        $country = $this->modelsManager->fetchByIdentifier(
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

        $user = $this->modelsManager->fetchByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO strict_owners (id, name) VALUES (1, 'anna')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO strict_profiles (id, owner_id, handle) VALUES (1, 1, 'anna-handle')",
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO strict_owners (id, name) VALUES (500, 'lazy')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO strict_profiles (id, owner_id, handle) VALUES (600, 500, 'lazy-handle')",
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO strict_owners (id, name) VALUES (510, 'force')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO strict_profiles (id, owner_id, handle) VALUES (610, 510, 'force-handle')",
            native: true,
        );

        $owner = $this->modelsManager->fetchByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO cascade_groups (id, name) VALUES (1200, 'g')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_tags (id, name) VALUES (1300, 'red')",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO cascade_group_tag (group_id, tag_id) VALUES (1200, 1300)',
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 1200,
        );

        (void) $this->modelsManager->save(
            model: $group,
            forceMaterialize: true,
        );

        self::assertEquals(
            1,
            $this->countRowsIn(
                table: 'cascade_group_tag',
                where: 'group_id = 1200 AND tag_id = 1300',
            ),
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

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1500,
        );

        $user->countryId = null;

        (void) $this->modelsManager->save($user);

        $refetched = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1500,
        );

        self::assertNull(
            $refetched->countryId,
        );
    }

    public function testCascadeDeleteSkipsBelongsToWhenValueIsNull(): void
    {
        $this->connection->query(
            sql: "INSERT INTO cascade_bt_children (id, parent_id, label) VALUES (900, NULL, 'orphan')",
            native: true,
        );

        $child = $this->modelsManager->fetchByIdentifier(
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
        $this->connection->query(
            sql: "INSERT INTO cascade_bt_parents (id, name) VALUES (901, 'force-parent')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_bt_children (id, parent_id, label) VALUES (902, 901, 'force-child')",
            native: true,
        );

        $child = $this->modelsManager->fetchByIdentifier(
            class: CascadeBelongsToChild::class,
            id: 902,
        );

        (void) $this->modelsManager->forceDelete($child);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_bt_parents',
                where: 'id = 901',
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

        $this->connection->query(
            sql: "INSERT INTO profiles (user_id, bio) VALUES (1600, 'x')",
            native: true,
        );

        $user = $this->modelsManager->fetchByIdentifier(
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

        $user = $this->modelsManager->fetchByIdentifier(
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
}
