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
use Tuxxedo\Model\Relation;

class HasManyThroughIntegrationTest extends AbstractModelIntegrationTestCase
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
            sql: "INSERT INTO countries (id, name, code) VALUES (2, 'Denmark', 'DK')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Bob', 'bob@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (3, 'Carla', 'carla@example.test', 1, 0, 0.0, 2)",
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

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (12, 2, 'Bob one', '', 'draft', 0, '0.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, viewCount, rating) VALUES (13, 3, 'Carla one', '', 'published', 0, '0.00')",
            native: true,
        );
    }

    /**
     * @return Relation<Post>
     */
    private function swedenPosts(): Relation
    {
        $country = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $country->posts,
        );

        return $country->posts;
    }

    public function testHasManyThroughCountReflectsAllTargetRowsAcrossThroughRows(): void
    {
        self::assertSame(
            3,
            $this->swedenPosts()->count(),
        );
    }

    public function testHasManyThroughIterationHydratesAllPosts(): void
    {
        $titles = [];

        foreach ($this->swedenPosts() as $post) {
            $titles[] = $post->title;
        }

        \sort($titles);

        self::assertSame(
            [
                'Alice one',
                'Alice two',
                'Bob one',
            ],
            $titles,
        );
    }

    public function testHasManyThroughFilteredByWhereOnTargetTable(): void
    {
        $published = $this->swedenPosts()->where(
            column: 'status',
            value: 'published',
        );

        self::assertSame(
            2,
            $published->count(),
        );
    }

    public function testHasManyThroughIsolatesSourceRows(): void
    {
        $denmark = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 2,
        );

        self::assertInstanceOf(
            Relation::class,
            $denmark->posts,
        );

        self::assertSame(
            1,
            $denmark->posts->count(),
        );
    }

    public function testHasManyThroughEmptyWhenNoThroughRowsMatch(): void
    {
        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (3, 'Norway', 'NO')",
            native: true,
        );

        $norway = $this->modelsManager->fetchByIdentifier(
            class: Country::class,
            id: 3,
        );

        self::assertInstanceOf(
            Relation::class,
            $norway->posts,
        );

        self::assertSame(
            0,
            $norway->posts->count(),
        );
    }
}
