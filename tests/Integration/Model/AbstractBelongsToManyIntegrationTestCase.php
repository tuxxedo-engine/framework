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

abstract class AbstractBelongsToManyIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createRolesTable();
        $this->createUserRolePivot();

        $this->seedUser(
            id: 1,
            name: 'Alice',
        );

        $this->seedUser(
            id: 2,
            name: 'Bob',
        );

        $this->seedRole(
            id: 10,
            key: 'ADMIN',
            label: 'Administrator',
            sortOrder: 1,
        );

        $this->seedRole(
            id: 11,
            key: 'EDITOR',
            label: 'Editor',
            sortOrder: 2,
        );

        $this->seedRole(
            id: 12,
            key: 'VIEWER',
            label: 'Viewer',
            sortOrder: 3,
        );

        $this->seedUserRole(
            userId: 1,
            roleId: 10,
        );

        $this->seedUserRole(
            userId: 1,
            roleId: 11,
        );
    }

    private function seedUser(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();
    }

    private function seedRole(
        int $id,
        string $key,
        string $label,
        int $sortOrder,
    ): void {
        $this->connection->insert(
            table: 'roles',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'key', value: $key)
            ->set(column: 'label', value: $label)
            ->set(column: 'sortOrder', value: $sortOrder)
            ->execute();
    }

    private function seedUserRole(
        int $userId,
        int $roleId,
    ): void {
        $this->connection->insert(
            table: 'user_role',
        )
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'role_id', value: $roleId)
            ->execute();
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

        $roles->add($viewer);

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

        $roles->remove($admin);

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

        $roles->add($viewer);
        $roles->remove($viewer);

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

        $user->roles->add($viewer);

        (void) $this->modelsManager->save($user);

        $count = $this->connection->count(
            table: 'user_role',
        )
            ->where(column: 'user_id', value: 1)
            ->count();

        self::assertSame(
            3,
            $count,
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

        $user->roles->remove($editor);

        (void) $this->modelsManager->save($user);

        $count = $this->connection->count(
            table: 'user_role',
        )
            ->where(column: 'user_id', value: 1)
            ->where(column: 'role_id', value: 11)
            ->count();

        self::assertSame(
            0,
            $count,
        );
    }
}
