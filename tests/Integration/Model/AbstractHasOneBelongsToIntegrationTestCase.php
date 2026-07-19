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

use Fixture\Model\Profile;
use Fixture\Model\User;

abstract class AbstractHasOneBelongsToIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
    }

    private function seedUserWithProfile(): void
    {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: 1)
            ->set(column: 'name', value: 'Alice')
            ->set(column: 'email', value: 'alice@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();

        $this->connection->insert(
            table: 'profiles',
        )
            ->set(column: 'id', value: 10)
            ->set(column: 'user_id', value: 1)
            ->set(column: 'bio', value: 'Alice bio')
            ->execute();
    }

    public function testHasOneLoadsProfileOnLazyPropertyAccess(): void
    {
        $this->seedUserWithProfile();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertNotNull(
            $user->profile,
        );

        self::assertSame(
            10,
            $user->profile->id,
        );

        self::assertSame(
            'Alice bio',
            $user->profile->bio,
        );
    }

    public function testBelongsToLoadsParentUserOnLazyAccess(): void
    {
        $this->seedUserWithProfile();

        $profile = $this->modelsManager->fetchByIdentifier(
            class: Profile::class,
            id: 10,
        );

        self::assertNotNull(
            $profile->user,
        );

        self::assertSame(
            1,
            $profile->user->id,
        );

        self::assertSame(
            'Alice',
            $profile->user->name,
        );
    }

    public function testSaveNewUserAssignsAutoIncrementId(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $saved = $this->modelsManager->save($user);

        self::assertNotNull(
            $saved->id,
        );

        $reloaded = $this->modelsManager->findByIdentifier(
            class: User::class,
            id: $saved->id,
        );

        self::assertNotNull(
            $reloaded,
        );

        self::assertSame(
            'Alice',
            $reloaded->name,
        );
    }

    public function testSaveExistingUserUpdatesChangedColumns(): void
    {
        $this->seedUserWithProfile();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        $user->name = 'Renamed';

        (void) $this->modelsManager->save($user);

        $reloaded = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertSame(
            'Renamed',
            $reloaded->name,
        );
    }

    public function testFetchByIdentifierYieldsNullHasOneWhenChildDoesNotExist(): void
    {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: 2)
            ->set(column: 'name', value: 'Bob')
            ->set(column: 'email', value: 'bob@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();

        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 2,
        );

        self::assertNull(
            $user->profile,
        );
    }
}
