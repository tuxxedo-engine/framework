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

use Fixture\Model\Aggregate\AggregateNamedRoot;
use Fixture\Model\Aggregate\OnConnectionAuditModel;
use PHPUnit\Framework\TestCase;
use Support\Database\SchemaCleaner;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Hydrator\Hydrator as DatabaseHydrator;
use Tuxxedo\Model\DirtyTracker;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\ModelsManager;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Model\ValidationScope;
use Tuxxedo\Validator\Validator;

abstract class AbstractAggregateCrossConnectionIntegrationTestCase extends TestCase
{
    protected Container $container;

    protected ConnectionInterface $primary;

    protected ConnectionInterface $audit;

    protected function setUp(): void
    {
        $skipReason = $this->realDatabaseSkipReason();

        if ($skipReason !== null) {
            self::markTestSkipped($skipReason);
        }

        $this->container = new Container();
        $this->container->singleton(class: $this->container);

        $this->primary = $this->createPrimaryConnection();
        $this->audit = $this->createAuditConnection();

        $connectionManager = new ConnectionManager();
        $connectionManager->registerConnection(connection: $this->primary);
        $connectionManager->registerConnection(connection: $this->audit);

        $this->container->singleton(class: $connectionManager);
    }

    protected function tearDown(): void
    {
        if (isset($this->audit) && $this->audit->isConnected()) {
            SchemaCleaner::dropAllTables(
                connection: $this->audit,
            );

            $this->audit->close();
        }

        if (isset($this->primary) && $this->primary->isConnected()) {
            $this->primary->close();
        }
    }

    abstract protected function createPrimaryConnection(): ConnectionInterface;

    abstract protected function createAuditConnection(): ConnectionInterface;

    protected function realDatabaseSkipReason(): ?string
    {
        return null;
    }

    public function testAggregateSaveThrowsForEntityDeclaredOnDifferentConnection(): void
    {
        $manager = $this->createManagerOn(connection: $this->primary);

        $entity = new OnConnectionAuditModel();
        $entity->note = 'wrong-connection';

        try {
            (void) $manager->save(
                model: $entity,
                scope: ValidationScope::AGGREGATE,
            );

            self::fail('Expected ModelException for cross-connection aggregate save');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'connection "audit"',
                $exception->getMessage(),
            );
        }
    }

    public function testAggregateSaveThrowsForNonRootEntityDeclaredOnDifferentConnection(): void
    {
        $manager = $this->createManagerOn(connection: $this->primary);

        $root = new AggregateNamedRoot();
        $root->name = 'holds-audit';
        $root->audit = new OnConnectionAuditModel();
        $root->audit->note = 'nested';

        try {
            (void) $manager->save(
                model: $root,
                scope: ValidationScope::AGGREGATE,
            );

            self::fail('Expected ModelException for non-root cross-connection entity');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'at path "audit"',
                $exception->getMessage(),
            );
        }
    }

    public function testAggregateSavePassesWhenEntityConnectionMatches(): void
    {
        $manager = $this->createManagerOn(connection: $this->audit);

        $manager->createTable(
            modelClass: OnConnectionAuditModel::class,
        )->execute();

        $entity = new OnConnectionAuditModel();
        $entity->note = 'right-connection';

        $saved = $manager->save(
            model: $entity,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertSame('right-connection', $saved->note);
    }

    private function createManagerOn(
        ConnectionInterface $connection,
    ): ModelsManagerInterface {
        return new ModelsManager(
            container: $this->container,
            connection: $connection,
            metaData: new MetaData(
                adapter: new ReflectionMetaDataAdapter(),
            ),
            dirtyTracker: new DirtyTracker(),
            databaseHydrator: new DatabaseHydrator(
                container: $this->container,
            ),
            validator: new Validator(
                container: $this->container,
            ),
        );
    }
}
