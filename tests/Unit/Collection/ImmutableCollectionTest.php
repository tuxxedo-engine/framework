<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Collection;

use Fixtures\Collection\StringTestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Collection\CollectionException;
use Tuxxedo\Collection\IntCollection;
use Tuxxedo\Collection\StringCollection;

class ImmutableCollectionTest extends TestCase
{
    public function testImmutableExceptionViaOffsetSet(): void
    {
        $strings = StringCollection::fromEnum(StringTestEnum::class)->toImmutable();

        $this->expectException(CollectionException::class);
        $strings['abc'] = 'def';
    }

    public function testImmutableExceptionsViaOffsetUnset(): void
    {
        $strings = StringCollection::fromEnum(StringTestEnum::class)->toImmutable();

        $this->expectException(CollectionException::class);
        unset($strings[$strings->firstKey()]);
    }

    public function testImmutableOffsetExists(): void
    {
        $strings = StringCollection::fromEnum(StringTestEnum::class)->toImmutable();

        self::assertTrue(isset($strings[0]));
    }

    public function testImmutableSort(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $sortedInts = $ints->sort();

        self::assertFalse($ints === $sortedInts);
        self::assertSame($ints[0], $sortedInts[0]);
    }

    public function testImmutableSortKeys(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $sortedInts = $ints->sortKeys();

        self::assertFalse($ints === $sortedInts);
        self::assertSame($ints->key(), $sortedInts->key());
    }

    public function testImmutableReverse(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $sortedInts = $ints->reverse();

        self::assertFalse($ints === $sortedInts);
        self::assertSame($ints->first(), $sortedInts->last());
    }

    public function testImmutableReverseKeys(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $sortedInts = $ints->reverseKeys();

        self::assertFalse($ints === $sortedInts);
        self::assertSame($ints->firstKey(), $sortedInts->lastKey());
    }

    public function testImmutableToMutable(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $mutableInts = $ints->toMutable();

        self::assertSameSize($ints, $mutableInts);
    }

    public function testImmutableToArray(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();
        $mutableInts = $ints->toMutable();

        self::assertSame($ints->toArray(), $mutableInts->toArray());
    }

    public function testImmutableFirstLast(): void
    {
        $collection = IntCollection::from()->toImmutable();

        self::assertSame($collection->count(), 0);
        self::assertNull($collection->first());
        self::assertNull($collection->last());
    }

    public function testImmutableContains(): void
    {
        $ints = IntCollection::fromRange(1, 3)->toImmutable();

        self::assertFalse($ints->contains());
        self::assertFalse($ints->contains(42));
        self::assertTrue($ints->contains(3));

        $sameInts = clone $ints;

        self::assertTrue($ints->contains($sameInts));

        $sameInts = $sameInts->toMutable()->append(4);

        self::assertFalse($ints->contains($sameInts));

        $noInts = IntCollection::from()->toImmutable();

        self::assertFalse($noInts->contains(0));
    }

    public function testImmutableKeyValues(): void
    {
        $ints = IntCollection::fromRange(1, 5)->toImmutable();

        self::assertSame($ints->keys(), [0, 1, 2, 3, 4]);
        self::assertSame($ints->values(), [1, 2, 3, 4, 5]);
    }

    public function testImmutableIterate(): void
    {
        $i = 0;
        $collection = StringCollection::from('Foo', 'Bar')->toImmutable();

        foreach ($collection as $value) {
            self::assertTrue($collection->containsKey($collection->key()));
            self::assertTrue($collection->contains($value));

            $i++;
        }

        self::assertSame($collection->count(), $i);
    }

    public function testImmutableArrayAccess(): void
    {
        $nordics = StringCollection::from('DK', 'FI', 'IS', 'NO', 'SE');

        self::assertTrue(isset($nordics[1]));
        self::assertFalse(isset($nordics[5]));
    }

    public function testImmutableContainsKey(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz')->toImmutable();

        self::assertFalse($collection->containsKey());
        self::assertFalse($collection->containsKey(3));
        self::assertTrue($collection->containsKey(0, 2));
    }

    public function testImmutableCollectionParity(): void
    {
        $mutable = StringCollection::from('Foo', 'Bar', 'Baz');
        $immutable = $mutable->toImmutable();

        self::assertSame($mutable->count(), $immutable->count());
        self::assertSame($mutable->toArray(), $immutable->toArray());
        self::assertSame($mutable->keys(), $immutable->keys());
        self::assertSame($mutable->values(), $immutable->values());
        self::assertSame($mutable->first(), $immutable->first());
        self::assertSame($mutable->firstKey(), $immutable->firstKey());
        self::assertSame($mutable->last(), $immutable->last());
        self::assertSame($mutable->lastKey(), $immutable->lastKey());
        self::assertSame(isset($mutable[0]), isset($immutable[0]));
        self::assertSame($mutable[2], $immutable[2]);

        self::assertSame(
            (clone $mutable)->sort()->toArray(),
            $immutable->sort()->toArray(),
        );

        self::assertSame(
            (clone $mutable)->sortKeys()->toArray(),
            $immutable->sortKeys()->toArray(),
        );

        self::assertSame(
            (clone $mutable)->reverse()->toArray(),
            $immutable->reverse()->toArray(),
        );

        self::assertSame(
            (clone $mutable)->reverseKeys()->toArray(),
            $immutable->reverseKeys()->toArray(),
        );

        self::assertSame($mutable->current(), $immutable->current());
        self::assertSame($mutable->key(), $immutable->key());

        self::assertTrue($mutable->valid());
        self::assertTrue($immutable->valid());

        for ($i = 0; $i < $mutable->count(); $i++) {
            $mutable->next();
            $immutable->next();
        }

        self::assertFalse($mutable->valid());
        self::assertFalse($immutable->valid());

        $mutable->rewind();
        $immutable->rewind();

        self::assertTrue($mutable->valid());
        self::assertTrue($immutable->valid());

        self::assertFalse($mutable->contains());
        self::assertFalse($immutable->contains());
        self::assertFalse($mutable->contains(1337));
        self::assertFalse($immutable->contains(1337));
        self::assertTrue($mutable->contains('Foo'));
        self::assertTrue($immutable->contains('Foo'));
        self::assertTrue($mutable->contains('Foo', 'Bar'));
        self::assertTrue($immutable->contains('Foo', 'Bar'));

        self::assertFalse($mutable->containsKey());
        self::assertFalse($immutable->containsKey());
        self::assertFalse($mutable->containsKey(3));
        self::assertFalse($immutable->containsKey(3));
        self::assertTrue($mutable->containsKey(0));
        self::assertTrue($immutable->containsKey(0));
        self::assertTrue($mutable->containsKey(1, 2));
        self::assertTrue($immutable->containsKey(1, 2));
    }
}
