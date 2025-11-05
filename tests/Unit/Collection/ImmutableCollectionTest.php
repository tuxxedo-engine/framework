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

namespace Collection;

use Fixtures\Collection\StringTestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Collection\CollectionException;
use Tuxxedo\Collection\IntCollection;
use Tuxxedo\Collection\StringCollection;

// @todo Collection<>ImmutableCollection test parity
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

    public function testContainsKey(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz')->toImmutable();

        self::assertFalse($collection->containsKey());
        self::assertFalse($collection->containsKey(3));
    }
}
