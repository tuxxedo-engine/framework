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

namespace Unit\Cache;

use Fixture\Cache\Cache\Cache\Money;
use Fixture\Cache\Cache\Cache\Plan;
use Fixture\Cache\Cache\Cache\Priority;
use Fixture\Cache\Cache\Cache\Profile;
use Fixture\Cache\Cache\Cache\Suit;
use PHPUnit\Framework\TestCase;
use Support\Cache\InMemoryCacheDriver;
use Tuxxedo\Cache\Cache;
use Tuxxedo\Cache\CacheException;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class CacheTest extends TestCase
{
    private InMemoryCacheDriver $driver;
    private Cache $cache;

    protected function setUp(): void
    {
        $this->driver = new InMemoryCacheDriver(
            clock: new FixedClock(
                instant: Instant::fromUnixTimestamp(1000),
            ),
        );

        $this->cache = new Cache(
            driver: $this->driver,
            mapper: new Mapper(),
            allowedClasses: [
                Money::class,
            ],
        );
    }

    private function moveClockTo(
        int $timestamp,
    ): void {
        $this->driver->clock = new FixedClock(
            instant: Instant::fromUnixTimestamp($timestamp),
        );
    }

    public function testStringReturnsNullOnMiss(): void
    {
        self::assertNull($this->cache->string('missing'));
    }

    public function testStringReturnsStoredValue(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertSame('Kalle', $this->cache->string('name'));
    }

    public function testStringThrowsOnTypeMismatch(): void
    {
        $this->cache->set(
            key: 'name',
            value: 42,
        );

        self::expectException(CacheException::class);

        $this->cache->string('name');
    }

    public function testIntReturnsStoredValue(): void
    {
        $this->cache->set(
            key: 'visits',
            value: 42,
        );

        self::assertSame(42, $this->cache->int('visits'));
    }

    public function testIntThrowsOnTypeMismatch(): void
    {
        $this->cache->set(
            key: 'visits',
            value: '42',
        );

        try {
            $this->cache->int('visits');
        } catch (CacheException $exception) {
            self::assertStringContainsString('expected int, found string', $exception->getMessage());
        }
    }

    public function testFloatReturnsStoredValue(): void
    {
        $this->cache->set(
            key: 'ratio',
            value: 1.5,
        );

        self::assertSame(1.5, $this->cache->float('ratio'));
    }

    public function testFloatThrowsOnIntValue(): void
    {
        $this->cache->set(
            key: 'ratio',
            value: 1,
        );

        self::expectException(CacheException::class);

        $this->cache->float('ratio');
    }

    public function testBoolReturnsStoredValue(): void
    {
        $this->cache->set(
            key: 'enabled',
            value: false,
        );

        self::assertFalse($this->cache->bool('enabled'));
    }

    public function testBoolThrowsOnTypeMismatch(): void
    {
        $this->cache->set(
            key: 'enabled',
            value: 0,
        );

        self::expectException(CacheException::class);

        $this->cache->bool('enabled');
    }

    public function testEnumHydratesStringBackedEnum(): void
    {
        $this->cache->set(
            key: 'plan',
            value: Plan::PRO,
        );

        self::assertSame(
            Plan::PRO,
            $this->cache->enum(
                key: 'plan',
                enum: Plan::class,
            ),
        );
    }

    public function testEnumHydratesIntBackedEnum(): void
    {
        $this->cache->set(
            key: 'priority',
            value: Priority::HIGH,
        );

        self::assertSame(
            Priority::HIGH,
            $this->cache->enum(
                key: 'priority',
                enum: Priority::class,
            ),
        );
    }

    public function testEnumHydratesPureEnum(): void
    {
        $this->cache->set(
            key: 'suit',
            value: Suit::SPADES,
        );

        self::assertSame(
            Suit::SPADES,
            $this->cache->enum(
                key: 'suit',
                enum: Suit::class,
            ),
        );
    }

    public function testEnumReturnsNullOnMiss(): void
    {
        self::assertNull(
            $this->cache->enum(
                key: 'plan',
                enum: Plan::class,
            ),
        );
    }

    public function testEnumThrowsOnUnknownCase(): void
    {
        $this->cache->set(
            key: 'plan',
            value: 'enterprise',
        );

        self::expectException(CacheException::class);

        $this->cache->enum(
            key: 'plan',
            enum: Plan::class,
        );
    }

    public function testSetStoresBackedEnumAsBackingValue(): void
    {
        $this->cache->set(
            key: 'plan',
            value: Plan::PRO,
        );

        self::assertSame('pro', $this->cache->string('plan'));
    }

    public function testSetStoresPureEnumAsCaseName(): void
    {
        $this->cache->set(
            key: 'suit',
            value: Suit::HEARTS,
        );

        self::assertSame('HEARTS', $this->cache->string('suit'));
    }

    public function testArrayOfStringReturnsStoredArray(): void
    {
        $this->cache->set(
            key: 'tags',
            value: [
                'a' => 'php',
                'b' => 'cache',
            ],
        );

        self::assertSame(
            [
                'a' => 'php',
                'b' => 'cache',
            ],
            $this->cache->arrayOfString('tags'),
        );
    }

    public function testArrayOfStringReturnsNullOnMiss(): void
    {
        self::assertNull($this->cache->arrayOfString('tags'));
    }

    public function testArrayOfStringThrowsOnNonArray(): void
    {
        $this->cache->set(
            key: 'tags',
            value: 'php',
        );

        self::expectException(CacheException::class);

        $this->cache->arrayOfString('tags');
    }

    public function testArrayOfStringThrowsOnMixedElements(): void
    {
        $this->cache->set(
            key: 'tags',
            value: [
                'php',
                1,
            ],
        );

        try {
            $this->cache->arrayOfString('tags');
        } catch (CacheException $exception) {
            self::assertStringContainsString('expected array<string>, found array<int>', $exception->getMessage());
        }
    }

    public function testArrayOfIntReturnsStoredArray(): void
    {
        $this->cache->set(
            key: 'ids',
            value: [
                1,
                2,
            ],
        );

        self::assertSame(
            [
                1,
                2,
            ],
            $this->cache->arrayOfInt('ids'),
        );
    }

    public function testArrayOfIntReturnsNullOnMiss(): void
    {
        self::assertNull($this->cache->arrayOfInt('ids'));
    }

    public function testArrayOfFloatReturnsStoredArray(): void
    {
        $this->cache->set(
            key: 'rates',
            value: [
                1.5,
                2.5,
            ],
        );

        self::assertSame(
            [
                1.5,
                2.5,
            ],
            $this->cache->arrayOfFloat('rates'),
        );
    }

    public function testArrayOfFloatReturnsNullOnMiss(): void
    {
        self::assertNull($this->cache->arrayOfFloat('rates'));
    }

    public function testArrayOfBoolReturnsStoredArray(): void
    {
        $this->cache->set(
            key: 'flags',
            value: [
                true,
                false,
            ],
        );

        self::assertSame(
            [
                true,
                false,
            ],
            $this->cache->arrayOfBool('flags'),
        );
    }

    public function testArrayOfBoolReturnsNullOnMiss(): void
    {
        self::assertNull($this->cache->arrayOfBool('flags'));
    }

    public function testArrayOfEnumHydratesCasesAndKeepsKeys(): void
    {
        $this->cache->set(
            key: 'plans',
            value: [
                'monthly' => Plan::FREE,
                'yearly' => Plan::PRO,
            ],
        );

        self::assertSame(
            [
                'monthly' => Plan::FREE,
                'yearly' => Plan::PRO,
            ],
            $this->cache->arrayOfEnum(
                key: 'plans',
                enum: Plan::class,
            ),
        );
    }

    public function testArrayOfEnumReturnsNullOnMiss(): void
    {
        self::assertNull(
            $this->cache->arrayOfEnum(
                key: 'plans',
                enum: Plan::class,
            ),
        );
    }

    public function testArrayOfEnumThrowsOnInvalidElement(): void
    {
        $this->cache->set(
            key: 'plans',
            value: [
                true,
            ],
        );

        self::expectException(CacheException::class);

        $this->cache->arrayOfEnum(
            key: 'plans',
            enum: Plan::class,
        );
    }

    public function testObjectReturnsAllowedObject(): void
    {
        $money = new Money(
            amount: 100,
            currency: 'DKK',
        );

        $this->cache->set(
            key: 'balance',
            value: $money,
        );

        self::assertSame(
            $money,
            $this->cache->object(
                key: 'balance',
                class: Money::class,
            ),
        );
    }

    public function testObjectReturnsNullOnMiss(): void
    {
        self::assertNull(
            $this->cache->object(
                key: 'balance',
                class: Money::class,
            ),
        );
    }

    public function testObjectThrowsOnWrongClass(): void
    {
        $this->cache->set(
            key: 'balance',
            value: 100,
        );

        self::expectException(CacheException::class);

        $this->cache->object(
            key: 'balance',
            class: Money::class,
        );
    }

    public function testSetThrowsOnObjectOutsideAllowList(): void
    {
        try {
            $this->cache->set(
                key: 'profile',
                value: new Profile(),
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString(Profile::class, $exception->getMessage());
        }
    }

    public function testSetThrowsOnObjectOutsideAllowListInsideArray(): void
    {
        self::expectException(CacheException::class);

        $this->cache->set(
            key: 'profiles',
            value: [
                new Profile(),
            ],
        );
    }

    public function testSetThrowsOnNullInsideArray(): void
    {
        try {
            $this->cache->set(
                key: 'values',
                value: [
                    null,
                ],
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('"null"', $exception->getMessage());
        }
    }

    public function testReadThrowsOnStoredObjectOutsideAllowList(): void
    {
        $this->driver->store(
            key: 'profile',
            value: new Profile(),
            ttl: null,
        );

        self::expectException(CacheException::class);

        $this->cache->string('profile');
    }

    public function testReadThrowsOnStoredObjectOutsideAllowListInsideArray(): void
    {
        $this->driver->store(
            key: 'profiles',
            value: [
                new Profile(),
            ],
            ttl: null,
        );

        self::expectException(CacheException::class);

        $this->cache->arrayOfString('profiles');
    }

    public function testMapToMapsStoredArray(): void
    {
        $this->cache->set(
            key: 'profile',
            value: [
                'name' => 'Kalle',
                'age' => 40,
            ],
        );

        $profile = $this->cache->mapTo(
            key: 'profile',
            className: Profile::class,
        );

        self::assertInstanceOf(Profile::class, $profile);
        self::assertSame('Kalle', $profile->name);
    }

    public function testMapToReturnsNullOnMiss(): void
    {
        self::assertNull(
            $this->cache->mapTo(
                key: 'profile',
                className: Profile::class,
            ),
        );
    }

    public function testMapToArrayOfMapsStoredList(): void
    {
        $this->cache->set(
            key: 'profiles',
            value: [
                [
                    'name' => 'Kalle',
                    'age' => 40,
                ],
                [
                    'name' => 'Mads',
                    'age' => 30,
                ],
            ],
        );

        $profiles = $this->cache->mapToArrayOf(
            key: 'profiles',
            className: Profile::class,
        );

        self::assertCount(2, $profiles ?? []);
        self::assertSame('Mads', ($profiles ?? [])[1]->name);
    }

    public function testMapToArrayOfReturnsNullOnMiss(): void
    {
        self::assertNull(
            $this->cache->mapToArrayOf(
                key: 'profiles',
                className: Profile::class,
            ),
        );
    }

    public function testHasReturnsTrueForStoredKey(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertTrue($this->cache->has('name'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse($this->cache->has('name'));
    }

    public function testAddStoresWhenAbsent(): void
    {
        self::assertTrue(
            $this->cache->add(
                key: 'lock',
                value: 'first',
            ),
        );
    }

    public function testAddReturnsFalseWhenPresent(): void
    {
        $this->cache->set(
            key: 'lock',
            value: 'first',
        );

        self::assertFalse(
            $this->cache->add(
                key: 'lock',
                value: 'second',
            ),
        );
    }

    public function testAddKeepsExistingValue(): void
    {
        $this->cache->set(
            key: 'lock',
            value: 'first',
        );

        (void) $this->cache->add(
            key: 'lock',
            value: 'second',
        );

        self::assertSame('first', $this->cache->string('lock'));
    }

    public function testRememberCallsFactoryOnMiss(): void
    {
        self::assertSame(
            'computed',
            $this->cache->remember(
                key: 'value',
                factory: static fn (): string => 'computed',
            ),
        );
    }

    public function testRememberStoresFactoryResult(): void
    {
        $this->cache->remember(
            key: 'value',
            factory: static fn (): string => 'computed',
        );

        self::assertSame('computed', $this->cache->string('value'));
    }

    public function testRememberReturnsCachedValueWithoutCallingFactory(): void
    {
        $this->cache->set(
            key: 'value',
            value: 'cached',
        );

        self::assertSame(
            'cached',
            $this->cache->remember(
                key: 'value',
                factory: static fn (): string => 'computed',
            ),
        );
    }

    public function testRememberThrowsOnEnumValue(): void
    {
        self::expectException(CacheException::class);

        $this->cache->remember(
            key: 'plan',
            factory: static fn (): Plan => Plan::PRO,
        );
    }

    public function testRememberThrowsOnInvalidTtl(): void
    {
        self::expectException(CacheException::class);

        $this->cache->remember(
            key: 'value',
            factory: static fn (): string => 'computed',
            ttl: Duration::fromSeconds(0),
        );
    }

    public function testIncrementStartsAtZero(): void
    {
        self::assertSame(1, $this->cache->increment('visits'));
    }

    public function testIncrementAddsStep(): void
    {
        $this->cache->set(
            key: 'visits',
            value: 10,
        );

        self::assertSame(
            15,
            $this->cache->increment(
                key: 'visits',
                by: 5,
            ),
        );
    }

    public function testIncrementThrowsOnZeroStep(): void
    {
        try {
            $this->cache->increment(
                key: 'visits',
                by: 0,
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('must be greater than zero', $exception->getMessage());
        }
    }

    public function testIncrementThrowsOnInvalidTtl(): void
    {
        self::expectException(CacheException::class);

        $this->cache->increment(
            key: 'visits',
            ttl: Duration::fromSeconds(0),
        );
    }

    public function testIncrementThrowsOnNonInteger(): void
    {
        $this->cache->set(
            key: 'visits',
            value: 'many',
        );

        self::expectException(CacheException::class);

        $this->cache->increment('visits');
    }

    public function testDecrementSubtractsStep(): void
    {
        $this->cache->set(
            key: 'stock',
            value: 10,
        );

        self::assertSame(
            7,
            $this->cache->decrement(
                key: 'stock',
                by: 3,
            ),
        );
    }

    public function testDecrementGoesBelowZero(): void
    {
        self::assertSame(-1, $this->cache->decrement('stock'));
    }

    public function testDecrementThrowsOnNegativeStep(): void
    {
        self::expectException(CacheException::class);

        $this->cache->decrement(
            key: 'stock',
            by: -1,
        );
    }

    public function testDecrementThrowsOnInvalidTtl(): void
    {
        self::expectException(CacheException::class);

        $this->cache->decrement(
            key: 'stock',
            ttl: Duration::fromSeconds(0),
        );
    }

    public function testDeleteRemovesKey(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        $this->cache->delete('name');

        self::assertFalse($this->cache->has('name'));
    }

    public function testDeleteMissingKeyIsNoOp(): void
    {
        $this->cache->delete('missing');

        self::assertFalse($this->cache->has('missing'));
    }

    public function testSetPrependsPrefix(): void
    {
        $cache = new Cache(
            driver: $this->driver,
            mapper: new Mapper(),
            prefix: 'reports:',
        );

        $cache->set(
            key: 'daily',
            value: 1,
        );

        self::assertArrayHasKey('reports:daily', $this->driver->entries);
    }

    public function testClearOnlyRemovesOwnPrefix(): void
    {
        $reports = new Cache(
            driver: $this->driver,
            mapper: new Mapper(),
            prefix: 'reports:',
        );

        $reports->set(
            key: 'daily',
            value: 1,
        );

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        $reports->clear();

        self::assertTrue($this->cache->has('name'));
        self::assertFalse($reports->has('daily'));
    }

    public function testSetThrowsOnEmptyKey(): void
    {
        self::expectException(CacheException::class);

        $this->cache->set(
            key: '',
            value: 1,
        );
    }

    public function testSetThrowsOnControlCharacterInKey(): void
    {
        try {
            $this->cache->set(
                key: 'a' . \chr(10) . 'b',
                value: 1,
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('a\nb', $exception->getMessage());
        }
    }

    public function testSetAcceptsKeyOf255Bytes(): void
    {
        $this->cache->set(
            key: \str_repeat('k', 255),
            value: 1,
        );

        self::assertTrue($this->cache->has(\str_repeat('k', 255)));
    }

    public function testSetThrowsOnKeyLongerThan255Bytes(): void
    {
        self::expectException(CacheException::class);

        $this->cache->set(
            key: \str_repeat('k', 256),
            value: 1,
        );
    }

    public function testKeyLengthIncludesPrefix(): void
    {
        $cache = new Cache(
            driver: $this->driver,
            mapper: new Mapper(),
            prefix: 'abcd:',
        );

        self::expectException(CacheException::class);

        $cache->set(
            key: \str_repeat('k', 251),
            value: 1,
        );
    }

    public function testSetThrowsOnZeroTtl(): void
    {
        try {
            $this->cache->set(
                key: 'name',
                value: 'Kalle',
                ttl: Duration::fromSeconds(0),
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('must be positive', $exception->getMessage());
        }
    }

    public function testSetThrowsOnNegativeTtl(): void
    {
        self::expectException(CacheException::class);

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
            ttl: Duration::fromSeconds(10)->negate(),
        );
    }

    public function testAddThrowsOnInvalidTtl(): void
    {
        self::expectException(CacheException::class);

        (void) $this->cache->add(
            key: 'name',
            value: 'Kalle',
            ttl: Duration::fromSeconds(0),
        );
    }

    public function testValueIsAvailableBeforeTtlElapses(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
            ttl: Duration::fromSeconds(10),
        );

        $this->moveClockTo(1009);

        self::assertSame('Kalle', $this->cache->string('name'));
    }

    public function testValueExpiresWhenTtlElapses(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
            ttl: Duration::fromSeconds(10),
        );

        $this->moveClockTo(1010);

        self::assertNull($this->cache->string('name'));
    }
}
