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

abstract class AbstractBehaviorIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createCommentsTable();
        $this->createSentinelsTable();
    }

    private function seedComment(
        int $id,
        string $body,
        string $createdAt,
        ?string $deletedAt,
    ): void {
        $this->connection->insert(
            table: 'comments',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'post_id', value: 100)
            ->set(column: 'user_id', value: 200)
            ->set(column: 'body', value: $body)
            ->set(column: 'createdAt', value: $createdAt)
            ->set(column: 'deletedAt', value: $deletedAt)
            ->execute();
    }

    private function countComments(
        string $whereColumn,
        int|string $whereValue,
    ): int {
        return $this->connection->count(
            table: 'comments',
        )
            ->where(column: $whereColumn, value: $whereValue)
            ->count();
    }

    private function fetchSentinelState(
        int $id,
    ): ?string {
        $result = $this->connection->select(
            table: 'sentinels',
        )
            ->select('state')
            ->where(column: 'id', value: $id)
            ->limit(1)
            ->execute();

        if (\count($result) === 0) {
            return null;
        }

        $row = $result->fetchAssoc();

        $state = $row['state'] ?? null;

        return \is_string($state)
            ? $state
            : null;
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
        $this->seedComment(
            id: 1,
            body: 'hello',
            createdAt: '2026-01-01',
            deletedAt: null,
        );

        $comment = $this->modelsManager->fetchByIdentifier(
            class: Comment::class,
            id: 1,
        );

        (void) $this->modelsManager->delete($comment);

        self::assertSame(
            1,
            $this->connection->count(
                table: 'comments',
            )
                ->where(column: 'id', value: 1)
                ->whereNotNull(column: 'deletedAt')
                ->count(),
        );
    }

    public function testFindByIdentifierExcludesSoftDeletedByDefault(): void
    {
        $this->seedComment(
            id: 2,
            body: 'gone',
            createdAt: '2026-01-01',
            deletedAt: '2026-01-02',
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
        $this->seedComment(
            id: 3,
            body: 'gone',
            createdAt: '2026-01-01',
            deletedAt: '2026-01-02',
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
        $this->seedComment(
            id: 4,
            body: 'alive',
            createdAt: '2026-01-01',
            deletedAt: null,
        );

        $this->seedComment(
            id: 5,
            body: 'dead',
            createdAt: '2026-01-01',
            deletedAt: '2026-01-02',
        );

        $comments = \iterator_to_array(
            $this->modelsManager->findAll(Comment::class),
        );

        self::assertCount(
            1,
            $comments,
        );
    }

    public function testForceDeleteRemovesRowIgnoringSoftDelete(): void
    {
        $this->seedComment(
            id: 6,
            body: 'nuke',
            createdAt: '2026-01-01',
            deletedAt: null,
        );

        $comment = $this->modelsManager->fetchByIdentifier(
            class: Comment::class,
            id: 6,
        );

        (void) $this->modelsManager->forceDelete($comment);

        self::assertSame(
            0,
            $this->countComments(
                whereColumn: 'id',
                whereValue: 6,
            ),
        );
    }

    public function testFetchByIdentifierThrowsForSoftDeletedByDefault(): void
    {
        $this->seedComment(
            id: 7,
            body: 'ghost',
            createdAt: '2026-01-01',
            deletedAt: '2026-01-02',
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

        self::assertSame(
            'inserted',
            $this->fetchSentinelState(
                id: (int) $saved->id,
            ),
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

        self::assertSame(
            'updated',
            $this->fetchSentinelState(
                id: (int) $saved->id,
            ),
        );
    }
}
