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

use Fixture\Model\Role;
use Fixture\Model\User;
use Tuxxedo\Model\Relation;

class BelongsToManyIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createRolesTable();
        $this->createUserRolePivot();

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score) VALUES (2, 'Bob', 'bob@example.test', 1, 0, 0.0)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (10, 'ADMIN', 'Administrator', 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (11, 'EDITOR', 'Editor', 2)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO roles (id, \"key\", label, sortOrder) VALUES (12, 'VIEWER', 'Viewer', 3)",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (1, 10)',
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO user_role (user_id, role_id) VALUES (1, 11)',
            native: true,
        );
    }

    /**
     * @return Relation<Role>
     */
    private function aliceRoles(): Relation
    {
        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        return $user->roles;
    }

    public function testBelongsToManyRelationCountReflectsPivotRows(): void
    {
        self::assertSame(
            2,
            $this->aliceRoles()->count(),
        );
    }

    public function testBelongsToManyRelationIterationHydratesRolesViaPivotJoin(): void
    {
        $labels = [];

        foreach ($this->aliceRoles() as $role) {
            $labels[] = $role->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'Administrator',
                'Editor',
            ],
            $labels,
        );
    }

    public function testBelongsToManyRelationEmptyForUserWithoutPivotRows(): void
    {
        $bob = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 2,
        );

        self::assertInstanceOf(
            Relation::class,
            $bob->roles,
        );

        self::assertSame(
            0,
            $bob->roles->count(),
        );
    }

    public function testBelongsToManyRelationFilteredByWhereOnRelatedTable(): void
    {
        $filtered = $this->aliceRoles()->where(
            column: 'roles.key',
            value: 'ADMIN',
        );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testBelongsToManyInverseFromRoleToUsers(): void
    {
        $role = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 10,
        );

        self::assertInstanceOf(
            Relation::class,
            $role->users,
        );

        self::assertSame(
            1,
            $role->users->count(),
        );

        $user = $role->users->first();

        self::assertNotNull(
            $user,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );
    }

    public function testBelongsToManyRelationAddPushesToPendingAdds(): void
    {
        $roles = $this->aliceRoles();

        $viewer = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 12,
        );

        $roles->add(item: $viewer);

        self::assertContains(
            $viewer,
            $roles->pendingAdds,
        );
    }

    public function testBelongsToManyRelationRemovePushesToPendingRemoves(): void
    {
        $roles = $this->aliceRoles();

        $admin = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 10,
        );

        $roles->remove(item: $admin);

        self::assertContains(
            $admin,
            $roles->pendingRemoves,
        );
    }

    public function testBelongsToManyAddThenRemoveCancelsPendingAdd(): void
    {
        $roles = $this->aliceRoles();

        $viewer = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 12,
        );

        $roles->add(item: $viewer);
        $roles->remove(item: $viewer);

        self::assertNotContains(
            $viewer,
            $roles->pendingAdds,
        );

        self::assertNotContains(
            $viewer,
            $roles->pendingRemoves,
        );
    }

    public function testSaveWithPendingAttachInsertsPivotRow(): void
    {
        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        $viewer = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 12,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        $user->roles->add(item: $viewer);

        (void) $this->modelsManager->save(model: $user);

        $row = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM user_role WHERE user_id = 1',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            3,
            $row['c'],
        );
    }

    public function testSaveWithPendingDetachRemovesPivotRow(): void
    {
        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        $editor = $this->modelsManager->fetchByIdentifier(
            class: Role::class,
            id: 11,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );

        $user->roles->remove(item: $editor);

        (void) $this->modelsManager->save(model: $user);

        $row = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM user_role WHERE user_id = 1 AND role_id = 11',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            0,
            $row['c'],
        );
    }
}
