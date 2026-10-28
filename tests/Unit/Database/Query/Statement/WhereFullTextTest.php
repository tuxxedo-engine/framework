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

namespace Unit\Database\Query\Statement;

use PHPUnit\Framework\TestCase;
use Support\Database\StubConnection;
use Support\Database\StubDialect;
use Support\Database\StubResultSet;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\SelectStatement;
use Tuxxedo\Database\SqlException;

class WhereFullTextTest extends TestCase
{
    private function connection(
        bool $indexExists = true,
    ): StubConnection {
        return new StubConnection(
            dialectImpl: new StubDialect(),
            queryHandler: static fn (string $sql): StubResultSet => new StubResultSet(
                firstRow: [
                    $indexExists,
                ],
            ),
        );
    }

    public function testIndexIsVerifiedOncePerConnectionAndTable(): void
    {
        $connection = $this->connection();

        foreach (['mint', 'ice'] as $search) {
            (new SelectStatement(
                table: 'articles',
                connection: $connection,
            ))
                ->whereFullText(
                    columns: [
                        'search_text',
                    ],
                    search: $search,
                );
        }

        self::assertCount(1, $connection->recordedQueries);
    }

    public function testMissingIndexThrowsWithIndexName(): void
    {
        try {
            (new SelectStatement(
                table: 'articles',
                connection: $this->connection(
                    indexExists: false,
                ),
            ))
                ->whereFullText(
                    columns: [
                        'search_text',
                    ],
                    search: 'mint',
                );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('articles_search_text_ft', $exception->getMessage());
        }
    }

    public function testQualifiedColumnsUseTheirOwnTable(): void
    {
        try {
            (new SelectStatement(
                table: 'products',
                connection: $this->connection(
                    indexExists: false,
                ),
            ))
                ->orWhereFullText(
                    columns: [
                        'translations.search_text',
                    ],
                    search: 'mint',
                );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('translations_search_text_ft', $exception->getMessage());
        }
    }

    public function testColumnsFromDifferentTablesThrow(): void
    {
        try {
            (new SelectStatement(
                table: 'articles',
                connection: $this->connection(),
            ))
                ->whereFullText(
                    columns: [
                        'search_text',
                        'translations.search_text',
                    ],
                    search: 'mint',
                );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('single table', $exception->getMessage());
        }
    }

    public function testStatementWithoutConnectionThrows(): void
    {
        $this->expectException(DatabaseException::class);

        (new SelectStatement(
            table: 'articles',
        ))
            ->whereFullText(
                columns: [
                    'search_text',
                ],
                search: 'mint',
            );
    }
}
