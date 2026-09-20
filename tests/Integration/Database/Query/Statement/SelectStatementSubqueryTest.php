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

namespace Integration\Database\Query\Statement;

use PHPUnit\Framework\TestCase;
use Support\Database\SqliteConnectionFactory;
use Tuxxedo\Database\Driver\ConnectionInterface;

class SelectStatementSubqueryTest extends TestCase
{
    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->connection = SqliteConnectionFactory::create();
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testSelectSubqueryRendersAliasedAggregate(): void
    {
        $subquery = $this->connection->select('tags')
            ->select('COUNT(*)')
            ->whereColumn(
                column: 'tags.owner_id',
                other: 'owners.id',
            );

        $statement = $this->connection->select('owners')
            ->selectSubquery(
                subquery: $subquery,
                alias: 'tags_count',
            );

        $result = $statement->compile();

        self::assertStringContainsString('SELECT', $result->sql);
        self::assertStringContainsString('AS "tags_count"', $result->sql);
        self::assertStringContainsString('COUNT(*)', $result->sql);
        self::assertStringContainsString('FROM "owners"', $result->sql);
    }

    public function testSelectSubqueryPrependsStarWhenNoBaseColumns(): void
    {
        $subquery = $this->connection->select('tags')
            ->select('COUNT(*)');

        $statement = $this->connection->select('owners')
            ->selectSubquery(
                subquery: $subquery,
                alias: 'tags_count',
            );

        $result = $statement->compile();

        self::assertStringContainsString('SELECT *,', $result->sql);
        self::assertStringContainsString('AS "tags_count"', $result->sql);
    }

    public function testSelectSubqueryPrependsQualifiedStarWhenJoinsPresentAndNoBaseColumns(): void
    {
        $subquery = $this->connection->select('tags')
            ->select('COUNT(*)');

        $statement = $this->connection->select('owners')
            ->innerJoin(
                table: 'profiles',
                first: 'owners.id',
                second: 'profiles.owner_id',
            )
            ->selectSubquery(
                subquery: $subquery,
                alias: 'tags_count',
            );

        $result = $statement->compile();

        self::assertStringContainsString('SELECT "owners".*,', $result->sql);
        self::assertStringContainsString('AS "tags_count"', $result->sql);
    }

    public function testSelectSubqueryNamespacesParameters(): void
    {
        $subquery = $this->connection->select('tags')
            ->select('COUNT(*)')
            ->where(
                column: 'tags.kind',
                value: 'topic',
            );

        $statement = $this->connection->select('owners')
            ->selectSubquery(
                subquery: $subquery,
                alias: 'topic_tags_count',
            )
            ->where(
                column: 'owners.status',
                value: 'active',
            );

        $result = $statement->compile();

        self::assertContains('topic', $result->parameters);
        self::assertContains('active', $result->parameters);
        self::assertSame(2, \sizeof($result->parameters));
    }

    public function testSelectSubqueryDoesNotCollideWithWhereExists(): void
    {
        $selectSub = $this->connection->select('tags')
            ->select('COUNT(*)')
            ->where(
                column: 'tags.kind',
                value: 'topic',
            );

        $existsSub = $this->connection->select('orders')
            ->where(
                column: 'orders.status',
                value: 'paid',
            );

        $statement = $this->connection->select('owners')
            ->selectSubquery(
                subquery: $selectSub,
                alias: 'topic_tags_count',
            )
            ->whereExists(
                subquery: $existsSub,
            );

        $result = $statement->compile();

        self::assertContains('topic', $result->parameters);
        self::assertContains('paid', $result->parameters);
        self::assertSame(2, \sizeof($result->parameters));
    }
}
