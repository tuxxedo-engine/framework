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

use Fixture\Model\Comment;
use Fixture\Model\Sentinel;
use Fixture\Model\User;
use Tuxxedo\Model\ModelException;

class BehaviorIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createCommentsTable();

        $this->connection->query(
            sql: 'CREATE TABLE sentinels (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'state TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );
    }

    public function testCreatedAtPopulatedOnInsert(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $saved = $this->modelsManager->save($user);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $saved->createdAt,
        );
    }

    public function testUpdatedAtPopulatedOnInsert(): void
    {
        $user = new User();
        $user->name = 'Bob';
        $user->email = 'bob@example.test';

        $saved = $this->modelsManager->save($user);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $saved->updatedAt,
        );
    }

    public function testUpdatedAtBumpedOnUpdateAndCreatedAtPreserved(): void
    {
        $user = new User();
        $user->name = 'Carla';
        $user->email = 'carla@example.test';

        $saved = $this->modelsManager->save($user);
        $originalCreatedAt = $saved->createdAt;
        $originalUpdatedAt = $saved->updatedAt;

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $originalCreatedAt,
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $originalUpdatedAt,
        );

        \usleep(microseconds: 1_100_000);

        $saved->name = 'Carla Renamed';
        (void) $this->modelsManager->save($saved);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $saved->updatedAt,
        );

        self::assertSame(
            $originalCreatedAt->format(format: 'Y-m-d H:i:s'),
            $saved->createdAt?->format(format: 'Y-m-d H:i:s'),
        );

        self::assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $saved->updatedAt->getTimestamp(),
        );
    }

    public function testSaveUnchangedExistingUserDoesNotBumpUpdatedAt(): void
    {
        $user = new User();
        $user->name = 'Dave';
        $user->email = 'dave@example.test';

        $saved = $this->modelsManager->save($user);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $saved->updatedAt,
        );

        $originalTimestamp = $saved->updatedAt->getTimestamp();

        \usleep(microseconds: 1_100_000);

        (void) $this->modelsManager->save($saved);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $saved->updatedAt,
        );

        self::assertSame(
            $originalTimestamp,
            $saved->updatedAt->getTimestamp(),
        );
    }

    public function testSoftDeleteSetsDeletedAtWithoutRemovingRow(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (1, 100, 200, 'hello', '2026-01-01', NULL)",
            native: true,
        );

        $comment = $this->modelsManager->fetchByIdentifier(
            class: Comment::class,
            id: 1,
        );

        (void) $this->modelsManager->delete($comment);

        $row = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM comments WHERE id = 1 AND deletedAt IS NOT NULL',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            1,
            $row['c'] ?? 0,
        );
    }

    public function testFindByIdentifierExcludesSoftDeletedByDefault(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (2, 100, 200, 'gone', '2026-01-01', '2026-01-02')",
            native: true,
        );

        $result = $this->modelsManager->findByIdentifier(
            class: Comment::class,
            id: 2,
        );

        self::assertNull(
            $result,
        );
    }

    public function testFindByIdentifierWithIncludeDeletedReturnsSoftDeleted(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (3, 100, 200, 'gone', '2026-01-01', '2026-01-02')",
            native: true,
        );

        $result = $this->modelsManager->findByIdentifier(
            class: Comment::class,
            id: 3,
            includeDeleted: true,
        );

        self::assertInstanceOf(
            Comment::class,
            $result,
        );
    }

    public function testFindAllExcludesSoftDeletedRows(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (4, 100, 200, 'alive', '2026-01-01', NULL)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (5, 100, 200, 'dead', '2026-01-01', '2026-01-02')",
            native: true,
        );

        $comments = \iterator_to_array(
            $this->modelsManager->findAll(class: Comment::class),
        );

        self::assertCount(
            1,
            $comments,
        );
    }

    public function testForceDeleteRemovesRowIgnoringSoftDelete(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (6, 100, 200, 'nuke', '2026-01-01', NULL)",
            native: true,
        );

        $comment = $this->modelsManager->fetchByIdentifier(
            class: Comment::class,
            id: 6,
        );

        (void) $this->modelsManager->forceDelete($comment);

        $row = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM comments WHERE id = 6',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $row['c'] ?? 0,
        );
    }

    public function testFetchByIdentifierThrowsForSoftDeletedByDefault(): void
    {
        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body, createdAt, deletedAt) VALUES (7, 100, 200, 'ghost', '2026-01-01', '2026-01-02')",
            native: true,
        );

        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByIdentifier(
            class: Comment::class,
            id: 7,
        );
    }

    public function testCustomBehaviorSetsStateOnInsert(): void
    {
        $sentinel = new Sentinel();
        $sentinel->state = 'ignored';

        $saved = $this->modelsManager->save($sentinel);

        self::assertSame(
            'inserted',
            $saved->state,
        );

        $row = $this->connection->query(
            sql: 'SELECT state FROM sentinels WHERE id = ' . (int) $saved->id,
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'inserted',
            $row['state'] ?? null,
        );
    }

    public function testCustomBehaviorSetsStateOnUpdate(): void
    {
        $sentinel = new Sentinel();
        $saved = $this->modelsManager->save($sentinel);

        $saved->state = 'about to change';

        (void) $this->modelsManager->save($saved);

        self::assertSame(
            'updated',
            $saved->state,
        );

        $row = $this->connection->query(
            sql: 'SELECT state FROM sentinels WHERE id = ' . (int) $saved->id,
            native: true,
        )->fetchAssoc();

        self::assertSame(
            'updated',
            $row['state'] ?? null,
        );
    }
}
