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

abstract class AbstractCascadeActionIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCascadeGroupsTable();
        $this->createCascadeChildrenTable();
        $this->createCascadeHasOneChildrenTable();
        $this->createCascadeHasOneRestrictChildrenTable();
        $this->createCascadeTagsTable();
        $this->createCascadeGroupTagPivot();
    }

    private function seedGroup(
        int $id,
        string $name = 'Alpha',
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
        string $label,
        ?int $autoGroupId = null,
        ?int $restrictGroupId = null,
        ?int $nullableGroupId = null,
        ?int $noActionGroupId = null,
    ): void {
        $this->connection->insert(
            table: 'cascade_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'auto_group_id', value: $autoGroupId)
            ->set(column: 'restrict_group_id', value: $restrictGroupId)
            ->set(column: 'nullable_group_id', value: $nullableGroupId)
            ->set(column: 'noaction_group_id', value: $noActionGroupId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedHasOneChild(
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

    private function seedHasOneRestrictChild(
        int $id,
        int $groupId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'cascade_hasone_restrict_children',
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

    private function seedGroupTag(
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

    private function countChildrenByColumn(
        string $column,
        int $value,
    ): int {
        return $this->connection->count(
            table: 'cascade_children',
        )
            ->where(column: $column, value: $value)
            ->count();
    }

    public function testHasManyCascadeDeletesAllChildren(): void
    {
        $this->seedGroup(
            id: 1,
        );

        $this->seedCascadeChild(
            id: 10,
            label: 'a',
            autoGroupId: 1,
        );

        $this->seedCascadeChild(
            id: 11,
            label: 'b',
            autoGroupId: 1,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 1,
        );

        (void) $this->modelsManager->delete($group);

        self::assertSame(
            0,
            $this->countChildrenByColumn(
                column: 'auto_group_id',
                value: 1,
            ),
        );
    }

    public function testHasManyRestrictThrowsWhenChildrenPresent(): void
    {
        $this->seedGroup(
            id: 2,
        );

        $this->seedCascadeChild(
            id: 20,
            label: 'r',
            restrictGroupId: 2,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 2,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->delete($group);
    }

    public function testHasManyRestrictAllowsDeleteWhenNoChildren(): void
    {
        $this->seedGroup(
            id: 3,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 3,
        );

        $deleted = $this->modelsManager->delete($group);

        self::assertTrue(
            $deleted,
        );

        self::assertSame(
            0,
            $this->connection->count(
                table: 'cascade_groups',
            )
                ->where(column: 'id', value: 3)
                ->count(),
        );
    }

    public function testHasManySetNullNullsChildrenForeignKeys(): void
    {
        $this->seedGroup(
            id: 4,
        );

        $this->seedCascadeChild(
            id: 40,
            label: 'n1',
            nullableGroupId: 4,
        );

        $this->seedCascadeChild(
            id: 41,
            label: 'n2',
            nullableGroupId: 4,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 4,
        );

        (void) $this->modelsManager->delete($group);

        self::assertSame(
            2,
            $this->connection->count(
                table: 'cascade_children',
            )
                ->whereNull(column: 'nullable_group_id')
                ->whereIn(
                    column: 'id',
                    values: [
                        40,
                        41,
                    ],
                )
                ->count(),
        );
    }

    public function testHasManyNoActionLeavesChildrenOrphaned(): void
    {
        $this->seedGroup(
            id: 5,
        );

        $this->seedCascadeChild(
            id: 50,
            label: 'na',
            noActionGroupId: 5,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 5,
        );

        (void) $this->modelsManager->delete($group);

        self::assertSame(
            1,
            $this->countChildrenByColumn(
                column: 'noaction_group_id',
                value: 5,
            ),
        );
    }

    public function testHasOneCascadeDeletesRelatedChild(): void
    {
        $this->seedGroup(
            id: 6,
        );

        $this->seedHasOneChild(
            id: 60,
            groupId: 6,
            label: 'one',
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 6,
        );

        (void) $this->modelsManager->delete($group);

        self::assertSame(
            0,
            $this->connection->count(
                table: 'cascade_hasone_children',
            )
                ->where(column: 'group_id', value: 6)
                ->count(),
        );
    }

    public function testBelongsToManyCascadeDeletesPivotRowsButPreservesTags(): void
    {
        $this->seedGroup(
            id: 7,
        );

        $this->seedCascadeTag(
            id: 70,
            name: 'red',
        );

        $this->seedCascadeTag(
            id: 71,
            name: 'blue',
        );

        $this->seedGroupTag(
            groupId: 7,
            tagId: 70,
        );

        $this->seedGroupTag(
            groupId: 7,
            tagId: 71,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 7,
        );

        (void) $this->modelsManager->delete($group);

        self::assertSame(
            0,
            $this->connection->count(
                table: 'cascade_group_tag',
            )
                ->where(column: 'group_id', value: 7)
                ->count(),
        );

        self::assertSame(
            2,
            $this->connection->count(
                table: 'cascade_tags',
            )
                ->whereIn(
                    column: 'id',
                    values: [
                        70,
                        71,
                    ],
                )
                ->count(),
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

        self::assertSame(
            1,
            $this->connection->count(
                table: 'cascade_hasone_children',
            )
                ->where(column: 'group_id', value: $saved->id)
                ->count(),
        );
    }

    public function testHasManyCascadeOnSavePersistsPendingChildren(): void
    {
        $this->seedGroup(
            id: 8,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 8,
        );

        self::assertInstanceOf(
            Relation::class,
            $group->autoChildren,
        );

        $child = new CascadeChild();
        $child->label = 'via cascade';

        $group->autoChildren->add($child);

        (void) $this->modelsManager->save($group);

        self::assertSame(
            1,
            $this->countChildrenByColumn(
                column: 'auto_group_id',
                value: 8,
            ),
        );
    }

    public function testBelongsToManyCascadeOnSaveFlushesPendingPivotRows(): void
    {
        $this->seedGroup(
            id: 9,
        );

        $this->seedCascadeTag(
            id: 90,
            name: 'green',
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 9,
        );

        $tag = $this->modelsManager->fetchById(
            class: CascadeTag::class,
            id: 90,
        );

        self::assertInstanceOf(
            Relation::class,
            $group->tags,
        );

        $group->tags->add($tag);

        (void) $this->modelsManager->save($group);

        self::assertSame(
            1,
            $this->connection->count(
                table: 'cascade_group_tag',
            )
                ->where(column: 'group_id', value: 9)
                ->where(column: 'tag_id', value: 90)
                ->count(),
        );
    }

    public function testHasOneRestrictThrowsWhenChildPresent(): void
    {
        $this->seedGroup(
            id: 100,
        );

        $this->seedHasOneRestrictChild(
            id: 100,
            groupId: 100,
            label: 'blocker',
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 100,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->delete($group);
    }

    public function testHasOneRestrictAllowsDeleteWhenNoChildExists(): void
    {
        $this->seedGroup(
            id: 101,
        );

        $group = $this->modelsManager->fetchById(
            class: CascadeGroup::class,
            id: 101,
        );

        $deleted = $this->modelsManager->delete($group);

        self::assertTrue(
            $deleted,
        );

        self::assertSame(
            0,
            $this->connection->count(
                table: 'cascade_groups',
            )
                ->where(column: 'id', value: 101)
                ->count(),
        );
    }
}
