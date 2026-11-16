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
use Fixture\Model\User;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

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

    public function testOrderByFullTextRelevanceRanksSavedModels(): void
    {
        $this->saveArticle(
            title: 'Cool Minty',
        );

        $this->saveArticle(
            title: 'Ice Mint',
        );

        $this->saveArticle(
            title: 'Mint Tea',
        );

        $titles = [];

        foreach (
            $this->modelsManager->query(Article::class)
                ->whereFullText(
                    columns: [
                        'search_text',
                    ],
                    search: 'mint',
                )
                ->orderByFullTextRelevance(
                    column: 'search_text',
                    search: 'mint',
                ) as $article
        ) {
            $titles[] = $article->title;
        }

        self::assertSame(
            [
                'Mint Tea',
                'Ice Mint',
                'Cool Minty',
            ],
            $titles,
        );
    }

    public function testOrderByFullTextRelevanceOnRelation(): void
    {
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();

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

        foreach (
            [
                10 => 'cool minty',
                11 => 'ice mint',
                12 => 'mint tea',
            ] as $id => $title
        ) {
            $this->connection->insert(
                table: 'posts',
            )
                ->set(column: 'id', value: $id)
                ->set(column: 'user_id', value: 1)
                ->set(column: 'title', value: $title)
                ->set(column: 'body', value: '')
                ->set(column: 'status', value: 'published')
                ->set(column: 'viewCount', value: 0)
                ->set(column: 'rating', value: '0.00')
                ->execute();
        }

        $user = $this->modelsManager->fetchById(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        $titles = [];

        foreach (
            $user->posts->orderByFullTextRelevance(
                column: 'title',
                search: 'mint',
                direction: 'DESC',
            ) as $post
        ) {
            $titles[] = $post->title;
        }

        self::assertSame(
            [
                'mint tea',
                'ice mint',
                'cool minty',
            ],
            $titles,
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
