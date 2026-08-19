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

abstract class AbstractHasOneThroughIntegrationTestCase extends AbstractModelIntegrationTestCase
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

        $this->seedUser(
            id: 1,
            name: 'Alice',
            countryId: 1,
        );

        $this->seedPost(
            id: 10,
            userId: 1,
            title: 'Alice one',
        );

        $this->seedPost(
            id: 11,
            userId: 1,
            title: 'Alice two',
        );
    }

    private function seedCountry(
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
            ->set(column: 'email', value: $name . '@example.test')
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
    ): void {
        $this->connection->insert(
            table: 'posts',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->set(column: 'body', value: '')
            ->set(column: 'status', value: 'published')
            ->set(column: 'viewCount', value: 0)
            ->set(column: 'rating', value: '0.00')
            ->execute();
    }

    public function testHasOneThroughResolvesTargetViaThroughJoin(): void
    {
        $country = $this->modelsManager->fetchById(
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
        $this->seedCountry(
            id: 2,
            name: 'Denmark',
            code: 'DK',
        );

        $this->seedUser(
            id: 2,
            name: 'Bo',
            countryId: 2,
        );

        $this->seedPost(
            id: 20,
            userId: 2,
            title: 'Bo one',
        );

        $denmark = $this->modelsManager->fetchById(
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
