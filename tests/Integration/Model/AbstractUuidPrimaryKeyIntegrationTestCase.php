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

use Fixture\Model\Uuid\UlidRecord;
use Fixture\Model\Uuid\UuidV4Explicit;
use Fixture\Model\Uuid\UuidV7Child;
use Fixture\Model\Uuid\UuidV7Owner;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Model\Relation;
use Tuxxedo\Model\ValidationScope;
use Tuxxedo\Uuid\Ulid;
use Tuxxedo\Uuid\UuidV7;

abstract class AbstractUuidPrimaryKeyIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUuidV7OwnersTable();
        $this->createUuidV7ChildrenTable();
        $this->createUlidRecordsTable();
        $this->createUuidV4ExplicitsTable();
    }

    public function testUuidV7PrimaryKeyAutoGeneratesOnInsert(): void
    {
        $owner = new UuidV7Owner();
        $owner->label = 'first';

        $saved = $this->modelsManager->save($owner);

        self::assertNotNull($saved->id);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $saved->id,
        );

        $refetched = $this->modelsManager->fetchById(
            class: UuidV7Owner::class,
            id: $saved->id,
        );

        self::assertSame($saved->id, $refetched->id);
        self::assertSame('first', $refetched->label);
    }

    public function testUuidV7PrimaryKeyRespectsCallerSetValue(): void
    {
        $preset = UuidV7::generate()->value;

        $owner = new UuidV7Owner();
        $owner->id = $preset;
        $owner->label = 'preset';

        $saved = $this->modelsManager->save($owner);

        self::assertSame($preset, $saved->id);
    }

    public function testUlidPrimaryKeyAutoGeneratesOnInsert(): void
    {
        $record = new UlidRecord();
        $record->label = 'first';

        $saved = $this->modelsManager->save($record);

        self::assertNotNull($saved->id);
        self::assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            $saved->id,
        );

        $refetched = $this->modelsManager->fetchById(
            class: UlidRecord::class,
            id: $saved->id,
        );

        self::assertSame($saved->id, $refetched->id);
        self::assertSame('first', $refetched->label);
    }

    public function testUlidPrimaryKeyRespectsCallerSetValue(): void
    {
        $preset = Ulid::generate()->value;

        $record = new UlidRecord();
        $record->id = $preset;
        $record->label = 'preset';

        $saved = $this->modelsManager->save($record);

        self::assertSame($preset, $saved->id);
    }

    public function testExplicitUuidVersionAnyDoesNotAutogenerate(): void
    {
        $record = new UuidV4Explicit();
        $record->label = 'no autogen';

        $this->expectException(DatabaseException::class);
        (void) $this->modelsManager->save($record);
    }

    public function testExplicitUuidVersionAnyAcceptsCallerSetValue(): void
    {
        $record = new UuidV4Explicit();
        $record->id = UuidV7::generate()->value;
        $record->label = 'caller supplied';

        $saved = $this->modelsManager->save($record);

        $refetched = $this->modelsManager->fetchById(
            class: UuidV4Explicit::class,
            id: $saved->id ?? throw new \LogicException('id not populated'),
        );

        self::assertSame($record->id, $refetched->id);
        self::assertSame('caller supplied', $refetched->label);
    }

    public function testAggregateSavePropagatesStringPrimaryKeyIntoChildForeignKey(): void
    {
        $owner = new UuidV7Owner();
        $owner->label = 'agg owner';
        $owner->children = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: UuidV7Child::class,
        );

        $first = new UuidV7Child();
        $first->label = 'first';

        $second = new UuidV7Child();
        $second->label = 'second';

        $owner->children->add($first);
        $owner->children->add($second);

        (void) $this->modelsManager->save(
            model: $owner,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($owner->id);
        self::assertSame($owner->id, $first->ownerId);
        self::assertSame($owner->id, $second->ownerId);

        $refetchedOwner = $this->modelsManager->fetchById(
            class: UuidV7Owner::class,
            id: $owner->id,
        );

        self::assertInstanceOf(
            Relation::class,
            $refetchedOwner->children,
        );

        self::assertSame(
            2,
            $refetchedOwner->children->count(),
        );

        $labels = [];

        foreach ($refetchedOwner->children as $child) {
            $labels[] = $child->label;
            self::assertSame($owner->id, $child->ownerId);
        }

        \sort($labels);

        self::assertSame(
            [
                'first',
                'second',
            ],
            $labels,
        );
    }

    public function testBelongsToLoadsUuidParentByStringForeignKey(): void
    {
        $owner = new UuidV7Owner();
        $owner->label = 'reverse owner';

        $savedOwner = $this->modelsManager->save($owner);
        self::assertNotNull($savedOwner->id);

        $child = new UuidV7Child();
        $child->ownerId = $savedOwner->id;
        $child->label = 'orphan turned kin';

        $savedChild = $this->modelsManager->save($child);
        self::assertNotNull($savedChild->id);

        $refetched = $this->modelsManager->fetchById(
            class: UuidV7Child::class,
            id: $savedChild->id,
        );

        self::assertInstanceOf(
            UuidV7Owner::class,
            $refetched->owner,
        );

        self::assertSame($savedOwner->id, $refetched->owner->id);
        self::assertSame('reverse owner', $refetched->owner->label);
    }
}
