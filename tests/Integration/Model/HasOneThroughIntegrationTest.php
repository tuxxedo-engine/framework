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

use Fixture\Model\Country;
use Fixture\Model\Post;

class HasOneThroughIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCountriesTable();
        $this->createUsersTable();
        $this->createPostsTable();

        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (10, 1, 'Alice one', '', 'published', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (11, 1, 'Alice two', '', 'published', 0, '0.00')",
            native: true,
        );
    }

    public function testHasOneThroughResolvesTargetViaThroughJoin(): void
    {
        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 1,
        );

        self::assertInstanceOf(
            Post::class,
            $country->firstPost,
        );

        self::assertSame(
            10,
            $country->firstPost->id,
        );

        self::assertSame(
            'Alice one',
            $country->firstPost->title,
        );
    }

    public function testHasOneThroughIsolatesSourceRows(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (2, 'Denmark', 'DK')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Bo', 'bo@example.test', 1, 0, 0.0, 2)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (20, 2, 'Bo one', '', 'published', 0, '0.00')",
            native: true,
        );

        $denmark = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 2,
        );

        self::assertInstanceOf(
            Post::class,
            $denmark->firstPost,
        );

        self::assertSame(
            'Bo one',
            $denmark->firstPost->title,
        );
    }
}
