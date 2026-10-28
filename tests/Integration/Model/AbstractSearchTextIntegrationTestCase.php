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

use Fixture\Model\Article;
use Fixture\Model\ArticleWithUnknownSource;
use Tuxxedo\Model\ModelException;

abstract class AbstractSearchTextIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelsManager
            ->createTable(Article::class)
            ->execute();
    }

    private function saveArticle(
        string $title,
        ?string $summary = null,
    ): Article {
        $article = new Article();
        $article->title = $title;
        $article->summary = $summary;

        return $this->modelsManager->save($article);
    }

    private function storedSearchText(
        int $id,
    ): mixed {
        $row = $this->connection->select(
            table: 'articles',
        )
            ->select('search_text')
            ->where(column: 'id', value: $id)
            ->execute()
            ->fetchAssoc();

        return $row['search_text'] ?? null;
    }

    public function testSaveFillsSearchTextFromSources(): void
    {
        $article = $this->saveArticle(
            title: 'Tabák Mix',
            summary: "L'moine & Ice",
        );

        self::assertNotNull($article->id);
        self::assertSame('tabak mix l moine ice', $this->storedSearchText($article->id));
    }

    public function testSaveSkipsNullSources(): void
    {
        $article = $this->saveArticle(
            title: 'Ice Mint',
        );

        self::assertNotNull($article->id);
        self::assertSame('ice mint', $this->storedSearchText($article->id));
    }

    public function testUpdateRefreshesSearchText(): void
    {
        $article = $this->saveArticle(
            title: 'Ice Mint',
        );

        $article->title = 'Cane Mint';
        $article = $this->modelsManager->save($article);

        self::assertNotNull($article->id);
        self::assertSame('cane mint', $this->storedSearchText($article->id));
    }

    public function testWhereFullTextFindsSavedModels(): void
    {
        $this->saveArticle(
            title: 'Tabák Mix',
        );

        $this->saveArticle(
            title: 'Ice Mint',
        );

        $found = $this->modelsManager->query(Article::class)
            ->whereFullText(
                columns: [
                    'search_text',
                ],
                search: 'TABÁK',
            )
            ->first();

        self::assertInstanceOf(Article::class, $found);
        self::assertSame('Tabák Mix', $found->title);
    }

    public function testOrWhereFullTextBroadensMatch(): void
    {
        $this->saveArticle(
            title: 'Tabák Mix',
        );

        $this->saveArticle(
            title: 'Ice Mint',
        );

        $this->saveArticle(
            title: 'Cane Mint',
        );

        $found = $this->modelsManager->query(Article::class)
            ->where(
                column: 'title',
                value: 'Cane Mint',
            )
            ->orWhereFullText(
                columns: [
                    'search_text',
                ],
                search: 'tabak',
            );

        self::assertSame(
            2,
            $found->count(),
        );
    }

    public function testUnknownSourceThrows(): void
    {
        $article = new ArticleWithUnknownSource();
        $article->title = 'Ice Mint';

        try {
            (void) $this->modelsManager->save($article);

            self::fail('Expected ModelException');
        } catch (ModelException $exception) {
            self::assertStringContainsString('"body"', $exception->getMessage());
        }
    }
}
