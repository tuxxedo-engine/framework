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

abstract class AbstractHasManyThroughIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCountriesTable();
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();

        $this->seedCountry(
            id: 1,
            name: 'Sweden',
            code: 'SE',
        );

        $this->seedCountry(
            id: 2,
            name: 'Denmark',
            code: 'DK',
        );

        $this->seedUser(
            id: 1,
            name: 'Alice',
            countryId: 1,
        );

        $this->seedUser(
            id: 2,
            name: 'Bob',
            countryId: 1,
        );

        $this->seedUser(
            id: 3,
            name: 'Carla',
            countryId: 2,
        );

        $this->seedPost(
            id: 10,
            userId: 1,
            title: 'Alice one',
            status: 'published',
        );

        $this->seedPost(
            id: 11,
            userId: 1,
            title: 'Alice two',
            status: 'published',
        );

        $this->seedPost(
            id: 12,
            userId: 2,
            title: 'Bob one',
            status: 'draft',
        );

        $this->seedPost(
            id: 13,
            userId: 3,
            title: 'Carla one',
            status: 'published',
        );
    }

    protected function seedCountry(
        int $id,
        string $name,
        string $code,
    ): void {
        $this->connection->insert(
            table: 'countries',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'code', value: $code)
            ->execute();
    }

    private function seedUser(
        int $id,
        string $name,
        int $countryId,
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
            ->set(column: 'country_id', value: $countryId)
            ->execute();
    }

    private function seedPost(
        int $id,
        int $userId,
        string $title,
        string $status,
    ): void {
        $this->connection->insert(
            table: 'posts',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->set(column: 'status', value: $status)
            ->set(column: 'viewCount', value: 0)
            ->set(column: 'rating', value: '0.00')
            ->execute();
    }

    /**
     * @return Relation<Post>
     */
    private function swedenPosts(): Relation
    {
        $country = $this->modelsManager->fetchById(
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
        $denmark = $this->modelsManager->fetchById(
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
        $this->seedCountry(
            id: 3,
            name: 'Norway',
            code: 'NO',
        );

        $norway = $this->modelsManager->fetchById(
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
