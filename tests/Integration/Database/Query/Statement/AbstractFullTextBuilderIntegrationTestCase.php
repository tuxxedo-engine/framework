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

use Tuxxedo\Database\Query\FullText\FullText;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\SqlException;

abstract class AbstractFullTextBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    private function createArticles(
        bool $withIndex = true,
    ): void {
        $table = $this->connection->createTable(
            table: 'articles',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->varchar(
            name: 'title',
            length: 100,
        );

        $table->text(
            name: 'search_text',
        );

        if ($withIndex) {
            $table->fullTextIndex('search_text');
        }

        $table->execute();

        $titles = [
            1 => 'Tabák Mix',
            2 => 'Ice Mint',
            3 => 'F-Line Cane Mint',
            4 => 'The Purple Stuff',
            5 => "L'moine",
        ];

        foreach ($titles as $id => $title) {
            $this->connection->insert(
                table: 'articles',
            )
                ->set(column: 'id', value: $id)
                ->set(column: 'title', value: $title)
                ->set(column: 'search_text', value: FullText::normalize($title))
                ->execute();
        }
    }

    /**
     * @return list<string>
     */
    private function titles(
        SelectStatementInterface $statement,
    ): array {
        $titles = [];

        foreach ($statement->orderBy('id')->execute() as $row) {
            $title = $row->properties['title'];

            self::assertIsString($title);

            $titles[] = $title;
        }

        return $titles;
    }

    private function search(
        string $search,
    ): SelectStatementInterface {
        return $this->connection->select(
            table: 'articles',
        )
            ->whereFullText(
                columns: [
                    'search_text',
                ],
                search: $search,
            );
    }

    public function testWhereFullTextMatchesWithoutAccentsOrCase(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'Tabák Mix',
            ],
            $this->titles(
                $this->search('TABAK'),
            ),
        );
    }

    public function testWhereFullTextMatchesTermsAsPrefixes(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'Ice Mint',
                'F-Line Cane Mint',
            ],
            $this->titles(
                $this->search('min'),
            ),
        );
    }

    public function testWhereFullTextRequiresEveryTerm(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'F-Line Cane Mint',
            ],
            $this->titles(
                $this->search('cane mint'),
            ),
        );
    }

    public function testWhereFullTextIgnoresTermsBelowMinimumLength(): void
    {
        $this->createArticles();

        self::assertSame(
            [],
            $this->titles(
                $this->search('mi'),
            ),
        );
    }

    public function testWhereFullTextTreatsOperatorCharactersAsSeparators(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'F-Line Cane Mint',
            ],
            $this->titles(
                $this->search('f-line'),
            ),
        );
    }

    public function testWhereFullTextMatchesStopwords(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'The Purple Stuff',
            ],
            $this->titles(
                $this->search('the'),
            ),
        );
    }

    public function testWhereFullTextMatchesApostropheSplitWords(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                "L'moine",
            ],
            $this->titles(
                $this->search('moine'),
            ),
        );
    }

    public function testOrWhereFullTextBroadensMatch(): void
    {
        $this->createArticles();

        self::assertSame(
            [
                'Tabák Mix',
                'Ice Mint',
            ],
            $this->titles(
                $this->connection->select(
                    table: 'articles',
                )
                    ->where(
                        column: 'id',
                        value: 1,
                    )
                    ->orWhereFullText(
                        columns: [
                            'search_text',
                        ],
                        search: 'ice',
                    ),
            ),
        );
    }

    public function testWhereFullTextThrowsWhenIndexIsMissing(): void
    {
        $this->createArticles(
            withIndex: false,
        );

        try {
            $this->search('mint');

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('articles_search_text_ft', $exception->getMessage());
        }
    }

    public function testAlterTableAddFullTextIndexEnablesSearch(): void
    {
        $this->createArticles(
            withIndex: false,
        );

        $this->connection->alterTable(
            table: 'articles',
        )
            ->addFullTextIndex(
                columns: [
                    'search_text',
                ],
            )
            ->execute();

        self::assertSame(
            [
                'Ice Mint',
            ],
            $this->titles(
                $this->search('ice'),
            ),
        );
    }
}
