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

namespace Unit\Collections;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Collections\Collection;
use Tuxxedo\Collections\ImmutableException;
use Tuxxedo\Collections\IntCollection;
use Tuxxedo\Collections\StringCollection;

class CollectionTest extends TestCase
{
    public function testCreate(): void
    {
        $collection = StringCollection::from('Hello', 'World');

        $this->assertSame($collection->current(), 'Hello');
        $this->assertSame($collection->current(), 'Hello');
    }

    public function testCount(): void
    {
        $collection = StringCollection::from('Kalle', 'Christopher', 'Ali');

        $this->assertSame($collection->count(), 3);
    }

    public function testIterate(): void
    {
        $i = 0;
        $collection = StringCollection::from('Foo', 'Bar');

        foreach ($collection as $value) {
            $this->assertTrue($collection->contains($value));

            $i++;
        }

        $this->assertSame($collection->count(), $i);
    }

    public function testMap(): void
    {
        $collection = StringCollection::from('Lorem', 'Ipsum');
        $collection->map(\strtoupper(...));

        $this->assertSame($collection->values(), ['LOREM', 'IPSUM']);

        $collection->map(
            static fn(string $v): string => \substr($v, 0, 1),
        );

        $this->assertSame($collection->values(), ['L', 'I']);
    }

    public function testFilter(): void
    {
        $scandinavia = ['DK', 'NO', 'SE'];
        $republics = ['FI', 'IS'];
        $nordics = $scandinavia + $republics;

        $collection = StringCollection::from(...$nordics);
        $collection->filter(
            static fn (string $v): bool => \in_array($v, $scandinavia),
        );

        $this->assertSame($collection->values(), $scandinavia);
        $this->assertSame($collection->count(), 3);
    }

    public function testMerge(): void
    {
        $scandinavia = StringCollection::from('DK', 'NO', 'SE');
        $republics = StringCollection::from('FI', 'IS');

        $nordics = (new Collection())
            ->merge($scandinavia)
            ->merge($republics);

        $this->assertSame($nordics->count(), 5);
        $this->assertTrue($nordics->contains('FI'));
    }

    public function testContains(): void
    {
        $range1 = IntCollection::fromRange(1, 10);
        $range2 = IntCollection::fromRange(1, 100);

        $this->assertTrue($range1->contains(8));
        $this->assertFalse($range1->contains(42));

        $this->assertFalse($range1->contains());
        $this->assertTrue($range2->contains($range1));
        $this->assertFalse($range1->contains($range2));
        $this->assertTrue($range1->contains($range1));
    }

    public function testArrayAccess(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $strings = StringCollection::from('Foo', 'Bar', 'Baz');

        $this->assertSame($ints[2], 3);
        $this->assertTrue(isset($strings[2]));

        unset($strings[2]);

        $this->assertFalse(isset($strings[2]));

        $strings[2] = 'qux';

        $this->assertSame($strings[2], 'qux');
    }

    public function testAppend(): void
    {
        $range = IntCollection::fromRange(1, 4);
        $range->append(5);

        $this->assertSame($range->last(), 5);
    }

    public function testPop(): void
    {
        $strings = StringCollection::from('Kalle', 'Christopher', 'Ali');
        $strings->pop();

        $this->assertFalse($strings->contains('Ali'));
    }

    public function testShift(): void
    {
        $ints = IntCollection::fromRange(0, 5);
        $ints->shift();

        $this->assertSame($ints->first(), 1);
    }

    public function testPrepend(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $ints->prepend(0);

        $this->assertSame($ints->first(), 0);
    }

    public function testClear(): void
    {
        $ints = IntCollection::fromRange(1, 5);

        $this->assertSame($ints->count(), 5);

        $ints->clear();

        $this->assertSame($ints->count(), 0);
    }

    public function testFirstLastKey(): void
    {
        $nordics = StringCollection::from('DK', 'FI', 'IS', 'NO', 'SE');

        $this->assertSame($nordics->firstKey(), 0);
        $this->assertSame($nordics->lastKey(), 4);

        $nordics->clear();

        $this->assertNull($nordics->firstKey());
        $this->assertNull($nordics->lastKey());
    }

    public function testFirstLastValue(): void
    {
        $nordics = StringCollection::from('DK', 'FI', 'IS', 'NO', 'SE');

        $this->assertSame($nordics->first(), 'DK');
        $this->assertSame($nordics->last(), 'SE');

        $nordics->clear();

        $this->assertNull($nordics->first());
        $this->assertNull($nordics->last());
    }

    public function testSortIntValues(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $ints->sort();

        $this->assertSame($ints->values(), [1, 2, 3, 4, 5]);

        $ints->reverse();

        $this->assertSame($ints->values(), [5, 4, 3, 2, 1]);
    }

    public function testSortIntKeys(): void
    {
        $ints = IntCollection::fromRange(1, 5);
        $ints->sortKeys();

        $this->assertSame($ints->keys(), [0, 1, 2, 3, 4]);

        $ints->reverseKeys();

        $this->assertSame($ints->keys(), [4, 3, 2, 1, 0]);
    }

    public function testSortString(): void {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz');
        $collection->sort();

        $this->assertSame($collection->values(), ['Bar', 'Baz', 'Foo']);

        $collection->reverse();

        $this->assertSame($collection->values(), ['Foo', 'Baz', 'Bar']);
    }

    public function testSortStringCaseInsensitive(): void
    {
        $collection = StringCollection::from('Foo', 'Bar', 'Baz');

        $collection->merge(
            (clone $collection)->map(\strtolower(...)),
        );

        $collection->sort();

        $this->assertSame(
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
        $this->assertFalse(StringCollection::fromEnum(StringTestEnum::class)->contains('IS'));
        $this->assertTrue(StringCollection::fromEnum(StringTestEnum::class)->contains('Iceland'));
        $this->assertSame(IntCollection::fromEnum(IntTestEnum::class)->count(), 5);
    }

    public function testImmutableExceptionViaOffsetSet()
    {
        $strings = StringCollection::fromEnum(StringTestEnum::class)->toImmutable();

        $this->expectException(ImmutableException::class);
        $strings['abc'] = 'def';
    }

    public function testImmutableExceptionsViaOffsetUnset()
    {
        $strings = StringCollection::fromEnum(StringTestEnum::class)->toImmutable();

        $this->expectException(ImmutableException::class);
        unset($strings[$strings->firstKey()]);
    }
}
