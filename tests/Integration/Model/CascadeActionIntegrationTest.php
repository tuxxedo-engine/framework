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

use Fixture\Model\CascadeChild;
use Fixture\Model\CascadeGroup;
use Fixture\Model\CascadeHasOneChild;
use Fixture\Model\CascadeTag;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class CascadeActionIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function seedGroup(int $id, string $name = 'Alpha'): void
    {
        $this->connection->query(
            sql: 'INSERT INTO cascade_groups (id, name) VALUES (' . $id . ", '" . $name . "')",
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

    public function testHasManyCascadeDeletesAllChildren(): void
    {
        $this->seedGroup(id: 1);

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, auto_group_id, label) VALUES (10, 1, 'a')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, auto_group_id, label) VALUES (11, 1, 'b')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 1,
        );

        (void) $this->modelsManager->delete($group);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_children',
                where: 'auto_group_id = 1',
            ),
        );
    }

    public function testHasManyRestrictThrowsWhenChildrenPresent(): void
    {
        $this->seedGroup(id: 2);

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, restrict_group_id, label) VALUES (20, 2, 'r')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 2,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->delete($group);
    }

    public function testHasManyRestrictAllowsDeleteWhenNoChildren(): void
    {
        $this->seedGroup(id: 3);

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 3,
        );

        $deleted = $this->modelsManager->delete($group);

        self::assertTrue(
            $deleted,
        );

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_groups',
                where: 'id = 3',
            ),
        );
    }

    public function testHasManySetNullNullsChildrenForeignKeys(): void
    {
        $this->seedGroup(id: 4);

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, nullable_group_id, label) VALUES (40, 4, 'n1')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, nullable_group_id, label) VALUES (41, 4, 'n2')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 4,
        );

        (void) $this->modelsManager->delete($group);

        self::assertEquals(
            2,
            $this->countRowsIn(
                table: 'cascade_children',
                where: 'nullable_group_id IS NULL AND id IN (40, 41)',
            ),
        );
    }

    public function testHasManyNoActionLeavesChildrenOrphaned(): void
    {
        $this->seedGroup(id: 5);

        $this->connection->query(
            sql: "INSERT INTO cascade_children (id, noaction_group_id, label) VALUES (50, 5, 'na')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 5,
        );

        (void) $this->modelsManager->delete($group);

        self::assertEquals(
            1,
            $this->countRowsIn(
                table: 'cascade_children',
                where: 'noaction_group_id = 5',
            ),
        );
    }

    public function testHasOneCascadeDeletesRelatedChild(): void
    {
        $this->seedGroup(id: 6);

        $this->connection->query(
            sql: "INSERT INTO cascade_hasone_children (id, group_id, label) VALUES (60, 6, 'one')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 6,
        );

        (void) $this->modelsManager->delete($group);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_hasone_children',
                where: 'group_id = 6',
            ),
        );
    }

    public function testBelongsToManyCascadeDeletesPivotRowsButPreservesTags(): void
    {
        $this->seedGroup(id: 7);

        $this->connection->query(
            sql: "INSERT INTO cascade_tags (id, name) VALUES (70, 'red')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO cascade_tags (id, name) VALUES (71, 'blue')",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO cascade_group_tag (group_id, tag_id) VALUES (7, 70)',
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO cascade_group_tag (group_id, tag_id) VALUES (7, 71)',
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 7,
        );

        (void) $this->modelsManager->delete($group);

        self::assertEquals(
            0,
            $this->countRowsIn(
                table: 'cascade_group_tag',
                where: 'group_id = 7',
            ),
        );

        self::assertEquals(
            2,
            $this->countRowsIn(
                table: 'cascade_tags',
                where: 'id IN (70, 71)',
            ),
        );
    }

    public function testHasOneCascadeOnSaveInsertsRelatedChild(): void
    {
        $group = new CascadeGroup();
        $group->name = 'Beta';

        $child = new CascadeHasOneChild();
        $child->label = 'brand new';
        $group->hasOneChild = $child;

        $saved = $this->modelsManager->save($group);

        self::assertNotNull(
            $saved->id,
        );

        self::assertEquals(
            1,
            $this->countRowsIn(
                table: 'cascade_hasone_children',
                where: 'group_id = ' . $saved->id,
            ),
        );
    }

    public function testHasManyCascadeOnSavePersistsPendingChildren(): void
    {
        $this->seedGroup(id: 8);

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 8,
        );

        self::assertInstanceOf(
            Relation::class,
            $group->autoChildren,
        );

        $child = new CascadeChild();
        $child->label = 'via cascade';

        $group->autoChildren->add(item: $child);

        (void) $this->modelsManager->save($group);

        self::assertEquals(
            1,
            $this->countRowsIn(
                table: 'cascade_children',
                where: 'auto_group_id = 8',
            ),
        );
    }

    public function testBelongsToManyCascadeOnSaveFlushesPendingPivotRows(): void
    {
        $this->seedGroup(id: 9);

        $this->connection->query(
            sql: "INSERT INTO cascade_tags (id, name) VALUES (90, 'green')",
            native: true,
        );

        $group = $this->modelsManager->fetchByIdentifier(
            class: CascadeGroup::class,
            id: 9,
        );

        $tag = $this->modelsManager->fetchByIdentifier(
            class: CascadeTag::class,
            id: 90,
        );

        self::assertInstanceOf(
            Relation::class,
            $group->tags,
        );

        $group->tags->add(item: $tag);

        (void) $this->modelsManager->save($group);

        self::assertEquals(
            1,
            $this->countRowsIn(
                table: 'cascade_group_tag',
                where: 'group_id = 9 AND tag_id = 90',
            ),
        );
    }
}
