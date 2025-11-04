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

use Fixtures\Collection\IntTestEnum;
use Fixtures\Collection\StringTestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Collection\Collection;
use Tuxxedo\Collection\IntCollection;
use Tuxxedo\Collection\StringCollection;

class CollectionTest extends TestCase
{
    public function testCreate(): void
    {
        $collection = StringCollection::from('Hello', 'World');

        self::assertSame($collection->current(), 'Hello');
        self::assertSame($collection->current(), 'Hello');
    }

    public function testCount(): void
    {
        $collection = StringCollection::from('Kalle', 'Christopher', 'Ali');

        self::assertSame($collection->count(), 3);
    }

    public function testIterate(): void
    {
        $i = 0;
        $collection = StringCollection::from('Foo', 'Bar');

        foreach ($collection as $value) {
            self::assertTrue($collection->containsKey($collection->key()));
            self::assertTrue($collection->contains($value));

            $i++;
        }

        self::assertSame($collection->count(), $i);
    }

    public function testContainsKey(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz');

        self::assertFalse($collection->containsKey());
        self::assertFalse($collection->containsKey(3));
    }

    public function testMap(): void
    {
        $collection = StringCollection::from('Lorem', 'Ipsum');
        $collection->map(\strtoupper(...));

        self::assertSame($collection->values(), ['LOREM', 'IPSUM']);

        $collection->map(
            static fn (string $v): string => \substr($v, 0, 1),
        );

        self::assertSame($collection->values(), ['L', 'I']);
    }

    public function testFilter(): void
    {
        $scandinavia = ['DK', 'NO', 'SE'];
        $republics = ['FI', 'IS'];
        $nordics = $scandinavia + $republics;

        $collection = StringCollection::from(...$nordics);
        $collection->filter(
            static fn (string $v): bool => \in_array($v, $scandinavia, true),
        );

        self::assertSame($collection->values(), $scandinavia);
        self::assertSame($collection->count(), 3);
    }

    public function testMerge(): void
    {
        $scandinavia = StringCollection::from('DK', 'NO', 'SE');
        $republics = StringCollection::from('FI', 'IS');

        /** @var Collection<int, string> $nordics */
        $nordics = new Collection();

        $nordics = $nordics->merge($scandinavia)->merge($republics);

        self::assertSame($nordics->count(), 5);
        self::assertTrue($nordics->contains('FI'));
    }

    public function testContains(): void
    {
        $range1 = IntCollection::fromRange(1, 10);
        $range2 = IntCollection::fromRange(1, 100);

        self::assertTrue($range1->contains(8));
        self::assertFalse($range1->contains(42));

        self::assertFalse($range1->contains());
        self::assertTrue($range2->contains($range1));
        self::assertFalse($range1->contains($range2));
        self::assertTrue($range1->contains($range1));
    }

    public function testArrayAccess(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $strings = StringCollection::from('Foo', 'Bar', 'Baz');

        self::assertSame($ints[2], 3);
        self::assertTrue(isset($strings[2]));

        unset($strings[2]);

        self::assertFalse(isset($strings[2]));

        $strings[2] = 'qux';

        self::assertSame($strings[2], 'qux');
    }

    public function testAppend(): void
    {
        $range = IntCollection::fromRange(1, 4);
        $range->append(5);

        self::assertSame($range->last(), 5);
    }

    public function testPop(): void
    {
        $strings = StringCollection::from('Kalle', 'Christopher', 'Ali');
        $strings->pop();

        self::assertFalse($strings->contains('Ali'));
    }

    public function testShift(): void
    {
        $ints = IntCollection::fromRange(0, 5);
        $ints->shift();

        self::assertSame($ints->first(), 1);
    }

    public function testPrepend(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $ints->prepend(0);

        self::assertSame($ints->first(), 0);
    }

    public function testClear(): void
    {
        $ints = IntCollection::fromRange(1, 5);

        self::assertSame($ints->count(), 5);

        $ints->clear();

        self::assertSame($ints->count(), 0);
    }

    public function testFirstLastKey(): void
    {
        $nordics = StringCollection::from('DK', 'FI', 'IS', 'NO', 'SE');

        self::assertSame($nordics->firstKey(), 0);
        self::assertSame($nordics->lastKey(), 4);

        $nordics->clear();

        self::assertNull($nordics->firstKey());
        self::assertNull($nordics->lastKey());
    }

    public function testFirstLastValue(): void
    {
        $nordics = StringCollection::from('DK', 'FI', 'IS', 'NO', 'SE');

        self::assertSame($nordics->first(), 'DK');
        self::assertSame($nordics->last(), 'SE');

        $nordics->clear();

        self::assertNull($nordics->first());
        self::assertNull($nordics->last());
    }

    public function testSortIntValues(): void
    {
        $ints = IntCollection::from(1, 2, 3, 4, 5);
        $ints->sort();

        self::assertSame($ints->values(), [1, 2, 3, 4, 5]);

        $ints->reverse();

        self::assertSame($ints->values(), [5, 4, 3, 2, 1]);
    }

    public function testSortIntKeys(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $ints->sortKeys();

        self::assertSame($ints->keys(), [0, 1, 2, 3, 4]);

        $ints->reverseKeys();

        self::assertSame($ints->keys(), [4, 3, 2, 1, 0]);
    }

    public function testSortString(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz');
        $collection->sort();

        self::assertSame($collection->values(), ['Bar', 'Baz', 'Foo']);

        $collection->reverse();

        self::assertSame($collection->values(), ['Foo', 'Baz', 'Bar']);
    }

    public function testSortStringCaseInsensitive(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz');

        $collection->merge(
            (clone $collection)->map(\strtolower(...)),
        );

        $collection->sort();

        self::assertSame(
            $collection->values(),
            [
                'Bar',
                'Baz',
                'Foo',
                'bar',
                'baz',
                'foo',
            ],
        );
    }

    public function testEnumRanges(): void
    {
        self::assertFalse(StringCollection::fromEnum(StringTestEnum::class)->contains('IS'));
        self::assertTrue(StringCollection::fromEnum(StringTestEnum::class)->contains('Iceland'));
        self::assertSame(IntCollection::fromEnum(IntTestEnum::class)->count(), 5);
    }
}
