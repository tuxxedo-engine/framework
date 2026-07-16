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

namespace Unit\Model;

use Fixture\Model\User;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\DirtyTracker;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\ModelException;

class DirtyTrackerTest extends TestCase
{
    private DirtyTracker $tracker;
    private ModelMetaDataInterface $meta;

    protected function setUp(): void
    {
        $this->tracker = new DirtyTracker();
        $this->meta = (new ReflectionMetaDataAdapter())->getModel(User::class);
    }

    public function testHasSnapshotFalseInitially(): void
    {
        $user = new User();

        self::assertFalse(
            $this->tracker->hasSnapshot($user),
        );
    }

    public function testHasSnapshotTrueAfterRecord(): void
    {
        $user = new User();

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        self::assertTrue(
            $this->tracker->hasSnapshot($user),
        );
    }

    public function testHasSnapshotFalseAfterForget(): void
    {
        $user = new User();

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $this->tracker->forgetSnapshot($user);

        self::assertFalse(
            $this->tracker->hasSnapshot($user),
        );
    }

    public function testGetDirtyColumnsReturnsFullStateWhenNoSnapshotExists(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $dirty = $this->tracker->getDirtyColumns(
            model: $user,
            metaData: $this->meta,
        );

        self::assertArrayHasKey(
            'name',
            $dirty,
        );

        self::assertSame(
            'Alice',
            $dirty['name'],
        );

        self::assertArrayHasKey(
            'email',
            $dirty,
        );
    }

    public function testGetDirtyColumnsReturnsEmptyArrayWhenModelUnchanged(): void
    {
        $user = new User();
        $user->name = 'Alice';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $dirty = $this->tracker->getDirtyColumns(
            model: $user,
            metaData: $this->meta,
        );

        self::assertSame(
            [],
            $dirty,
        );
    }

    public function testGetDirtyColumnsReturnsOnlyChangedColumnsAfterModification(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $user->name = 'Bob';

        $dirty = $this->tracker->getDirtyColumns(
            model: $user,
            metaData: $this->meta,
        );

        self::assertSame(
            [
                'name' => 'Bob',
            ],
            $dirty,
        );
    }

    public function testIsDirtyReturnsTrueWhenNoSnapshotExists(): void
    {
        $user = new User();

        self::assertTrue(
            $this->tracker->isDirty(
                model: $user,
                metaData: $this->meta,
            ),
        );
    }

    public function testIsDirtyReturnsFalseAfterSnapshotWithNoChanges(): void
    {
        $user = new User();
        $user->name = 'Alice';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        self::assertFalse(
            $this->tracker->isDirty(
                model: $user,
                metaData: $this->meta,
            ),
        );
    }

    public function testIsDirtyReturnsTrueAfterModification(): void
    {
        $user = new User();
        $user->name = 'Alice';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $user->email = 'alice@example.test';

        self::assertTrue(
            $this->tracker->isDirty(
                model: $user,
                metaData: $this->meta,
            ),
        );
    }

    public function testIsDirtyPropertyReturnsTrueWhenNoSnapshotExists(): void
    {
        $user = new User();

        self::assertTrue(
            $this->tracker->isDirtyProperty(
                model: $user,
                metaData: $this->meta,
                property: 'name',
            ),
        );
    }

    public function testIsDirtyPropertyReturnsFalseWhenPropertyUnchanged(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $user->email = 'renamed@example.test';

        self::assertFalse(
            $this->tracker->isDirtyProperty(
                model: $user,
                metaData: $this->meta,
                property: 'name',
            ),
        );
    }

    public function testIsDirtyPropertyReturnsTrueWhenPropertyChanged(): void
    {
        $user = new User();
        $user->name = 'Alice';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $user->name = 'Bob';

        self::assertTrue(
            $this->tracker->isDirtyProperty(
                model: $user,
                metaData: $this->meta,
                property: 'name',
            ),
        );
    }

    public function testIsDirtyPropertyThrowsOnUnknownProperty(): void
    {
        $user = new User();
        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $this->expectException(ModelException::class);

        (void) $this->tracker->isDirtyProperty(
            model: $user,
            metaData: $this->meta,
            property: 'nonexistentProperty',
        );
    }

    public function testSnapshotIsolationBetweenModelInstances(): void
    {
        $alice = new User();
        $alice->name = 'Alice';

        $bob = new User();
        $bob->name = 'Bob';

        $this->tracker->recordSnapshot(
            model: $alice,
            metaData: $this->meta,
        );

        self::assertTrue(
            $this->tracker->hasSnapshot($alice),
        );

        self::assertFalse(
            $this->tracker->hasSnapshot($bob),
        );
    }

    public function testRecordSnapshotOverwritesExistingSnapshot(): void
    {
        $user = new User();
        $user->name = 'Alice';

        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        $user->name = 'Bob';
        $this->tracker->recordSnapshot(
            model: $user,
            metaData: $this->meta,
        );

        self::assertFalse(
            $this->tracker->isDirty(
                model: $user,
                metaData: $this->meta,
            ),
        );

        $dirty = $this->tracker->getDirtyColumns(
            model: $user,
            metaData: $this->meta,
        );

        self::assertSame(
            [],
            $dirty,
        );
    }

    public function testForgetSnapshotOnUntrackedModelIsIdempotent(): void
    {
        $user = new User();

        $this->tracker->forgetSnapshot($user);

        self::assertFalse(
            $this->tracker->hasSnapshot($user),
        );
    }
}
