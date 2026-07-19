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

use PHPUnit\Framework\TestCase;
use Support\Database\SchemaCleaner;
use Support\Model\ModelSchemaProvider;
use Support\Model\ModelsManagerFactory;
use Support\Model\SqliteModelSchemaProvider;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Model\ModelsManagerInterface;

abstract class AbstractModelIntegrationTestCase extends TestCase
{
    protected ModelsManagerInterface $modelsManager;
    protected ConnectionInterface $connection;
    protected ModelSchemaProvider $schemaProvider;

    protected function setUp(): void
    {
        $skipReason = $this->realDatabaseSkipReason();

        if ($skipReason !== null) {
            self::markTestSkipped($skipReason);
        }

        $this->schemaProvider = $this->schemaProvider();
        $this->modelsManager = $this->createModelsManager();
        $this->connection = $this->modelsManager->connection;
    }

    protected function tearDown(): void
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            SchemaCleaner::dropAllTables(
                connection: $this->connection,
            );

            $this->connection->close();
        }
    }

    protected function createModelsManager(): ModelsManagerInterface
    {
        return ModelsManagerFactory::create();
    }

    protected function schemaProvider(): ModelSchemaProvider
    {
        return new SqliteModelSchemaProvider();
    }

    protected function realDatabaseSkipReason(): ?string
    {
        return null;
    }

    protected function createAllFixtureTables(): void
    {
        $this->createCountriesTable();
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();
        $this->createCommentsTable();
        $this->createTagsTable();
        $this->createRolesTable();
        $this->createCategoriesTable();
        $this->createPostTagPivot();
        $this->createUserRolePivot();
    }

    protected function createCountriesTable(): void
    {
        $this->executeSchema($this->schemaProvider->countriesSchemaSql());
    }

    protected function createUsersTable(): void
    {
        $this->executeSchema($this->schemaProvider->usersSchemaSql());
    }

    protected function createProfilesTable(): void
    {
        $this->executeSchema($this->schemaProvider->profilesSchemaSql());
    }

    protected function createPostsTable(): void
    {
        $this->executeSchema($this->schemaProvider->postsSchemaSql());
    }

    protected function createCommentsTable(): void
    {
        $this->executeSchema($this->schemaProvider->commentsSchemaSql());
    }

    protected function createTagsTable(): void
    {
        $this->executeSchema($this->schemaProvider->tagsSchemaSql());
    }

    protected function createRolesTable(): void
    {
        $this->executeSchema($this->schemaProvider->rolesSchemaSql());
    }

    protected function createCategoriesTable(): void
    {
        $this->executeSchema($this->schemaProvider->categoriesSchemaSql());
    }

    protected function createPostTagPivot(): void
    {
        $this->executeSchema($this->schemaProvider->postTagPivotSchemaSql());
    }

    protected function createUserRolePivot(): void
    {
        $this->executeSchema($this->schemaProvider->userRolePivotSchemaSql());
    }

    protected function createSentinelsTable(): void
    {
        $this->executeSchema($this->schemaProvider->sentinelsSchemaSql());
    }

    protected function createCascadeGroupsTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeGroupsSchemaSql());
    }

    protected function createCascadeChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeChildrenSchemaSql());
    }

    protected function createCascadeHasOneChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeHasOneChildrenSchemaSql());
    }

    protected function createCascadeHasOneRestrictChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeHasOneRestrictChildrenSchemaSql());
    }

    protected function createCascadeTagsTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeTagsSchemaSql());
    }

    protected function createCascadeGroupTagPivot(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeGroupTagPivotSchemaSql());
    }

    protected function createReadonlyRecordsTable(): void
    {
        $this->executeSchema($this->schemaProvider->readonlyRecordsSchemaSql());
    }

    protected function createSettingsTable(): void
    {
        $this->executeSchema($this->schemaProvider->settingsSchemaSql());
    }

    protected function createBulkParentsTable(): void
    {
        $this->executeSchema($this->schemaProvider->bulkParentsSchemaSql());
    }

    protected function createBulkChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->bulkChildrenSchemaSql());
    }

    protected function createOrphanParentsTable(): void
    {
        $this->executeSchema($this->schemaProvider->orphanParentsSchemaSql());
    }

    protected function createOrphanChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->orphanChildrenSchemaSql());
    }

    protected function createCascadeBelongsToParentsTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeBelongsToParentsSchemaSql());
    }

    protected function createCascadeBelongsToChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->cascadeBelongsToChildrenSchemaSql());
    }

    protected function createStrictOwnersTable(): void
    {
        $this->executeSchema($this->schemaProvider->strictOwnersSchemaSql());
    }

    protected function createStrictProfilesTable(): void
    {
        $this->executeSchema($this->schemaProvider->strictProfilesSchemaSql());
    }

    protected function createStrictChildrenTable(): void
    {
        $this->executeSchema($this->schemaProvider->strictChildrenSchemaSql());
    }

    protected function createRegionsTable(): void
    {
        $this->executeSchema($this->schemaProvider->regionsSchemaSql());
    }

    protected function createBranchesTable(): void
    {
        $this->executeSchema($this->schemaProvider->branchesSchemaSql());
    }

    protected function createWarehousesTable(): void
    {
        $this->executeSchema($this->schemaProvider->warehousesSchemaSql());
    }

    protected function createNullableThroughOwnersTable(): void
    {
        $this->executeSchema($this->schemaProvider->nullableThroughOwnersSchemaSql());
    }

    protected function createStrictThroughOwnersTable(): void
    {
        $this->executeSchema($this->schemaProvider->strictThroughOwnersSchemaSql());
    }

    private function executeSchema(
        string $sql,
    ): void {
        $this->connection->query(
            sql: $sql,
            native: true,
        );
    }
}
