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

namespace Integration\Cache\Driver;

use Fixture\Cache\Driver\Money;
use Fixture\Cache\Driver\Plan;
use Fixture\Cache\Driver\Profile;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Cache\Cache;
use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Temporal\Duration;

abstract class AbstractCacheDriverIntegrationTestCase extends TestCase
{
    protected CacheDriverInterface $driver;
    protected Cache $cache;
    protected string $namespace;

    abstract protected function skipReason(): ?string;

    abstract protected function createDriver(
        string $namespace,
    ): CacheDriverInterface;

    protected function setUp(): void
    {
        $reason = $this->skipReason();

        if ($reason !== null) {
            self::markTestSkipped($reason);
        }

        $this->namespace = 'tuxxedo-test-' . \bin2hex(\random_bytes(6)) . ':';
        $this->driver = $this->createDriver($this->namespace);
        $this->cache = $this->createCache(
            driver: $this->driver,
            prefix: '',
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->driver)) {
            $this->driver->clear('');
        }
    }

    /**
     * @param list<class-string> $allowedClasses
     */
    protected function createCache(
        CacheDriverInterface $driver,
        string $prefix,
        array $allowedClasses = [
            Money::class,
        ],
    ): Cache {
        return new Cache(
            driver: $driver,
            mapper: new Mapper(),
            prefix: $prefix,
            allowedClasses: $allowedClasses,
        );
    }

    public function testStringRoundTrips(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertSame('Kalle', $this->cache->string('name'));
    }

    public function testIntRoundTrips(): void
    {
        $this->cache->set(
            key: 'visits',
            value: -42,
        );

        self::assertSame(-42, $this->cache->int('visits'));
    }

    public function testFloatRoundTrips(): void
    {
        $this->cache->set(
            key: 'ratio',
            value: 0.1,
        );

        self::assertSame(0.1, $this->cache->float('ratio'));
    }

    public function testWholeFloatStaysFloat(): void
    {
        $this->cache->set(
            key: 'ratio',
            value: 2.0,
        );

        self::assertSame(2.0, $this->cache->float('ratio'));
    }

    public function testFalseRoundTrips(): void
    {
        $this->cache->set(
            key: 'enabled',
            value: false,
        );

        self::assertFalse($this->cache->bool('enabled'));
    }

    public function testNumericStringStaysString(): void
    {
        $this->cache->set(
            key: 'zip',
            value: '0042',
        );

        self::assertSame('0042', $this->cache->string('zip'));
    }

    public function testArrayKeepsKeys(): void
    {
        $value = [
            'name' => 'Kalle',
            3 => 'three',
        ];

        $this->cache->set(
            key: 'labels',
            value: $value,
        );

        self::assertSame($value, $this->cache->arrayOfString('labels'));
    }

    public function testNestedArrayMapsToObject(): void
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

    public function testEnumRoundTrips(): void
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

    public function testAllowedObjectRoundTrips(): void
    {
        $this->cache->set(
            key: 'balance',
            value: new Money(
                amount: 100,
                currency: 'DKK',
            ),
        );

        $balance = $this->cache->object(
            key: 'balance',
            class: Money::class,
        );

        self::assertInstanceOf(Money::class, $balance);
        self::assertSame(100, $balance->amount);
    }

    public function testReadRejectsObjectOutsideReaderAllowList(): void
    {
        $this->cache->set(
            key: 'balance',
            value: new Money(
                amount: 100,
                currency: 'DKK',
            ),
        );

        $reader = $this->createCache(
            driver: $this->driver,
            prefix: '',
            allowedClasses: [],
        );

        self::expectException(CacheException::class);

        $reader->string('balance');
    }

    public function testMissingKeyReadsAsNull(): void
    {
        self::assertNull($this->cache->string('missing'));
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
        self::assertFalse($this->cache->has('missing'));
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

    public function testIncrementCreatesMissingKey(): void
    {
        self::assertSame(1, $this->cache->increment('visits'));
    }

    public function testIncrementAddsToStoredInt(): void
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

    public function testIncrementedValueReadsBackAsInt(): void
    {
        $this->cache->increment(
            key: 'visits',
            by: 3,
        );

        self::assertSame(3, $this->cache->int('visits'));
    }

    public function testDecrementGoesBelowZero(): void
    {
        self::assertSame(
            -2,
            $this->cache->decrement(
                key: 'stock',
                by: 2,
            ),
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

    public function testIncrementThrowsOnOverflow(): void
    {
        $this->cache->set(
            key: 'visits',
            value: \PHP_INT_MAX,
        );

        self::expectException(CacheException::class);

        $this->cache->increment('visits');
    }

    public function testOverflowKeepsStoredValue(): void
    {
        $this->cache->set(
            key: 'visits',
            value: \PHP_INT_MAX,
        );

        try {
            $this->cache->increment('visits');
        } catch (CacheException) {
            self::assertSame(\PHP_INT_MAX, $this->cache->int('visits'));
        }
    }

    public function testDecrementThrowsOnOverflow(): void
    {
        $this->cache->set(
            key: 'visits',
            value: \PHP_INT_MIN,
        );

        self::expectException(CacheException::class);

        $this->cache->decrement('visits');
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

    public function testClearOnlyRemovesOwnPrefix(): void
    {
        $reports = $this->createCache(
            driver: $this->driver,
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

        self::assertFalse($reports->has('daily'));
        self::assertTrue($this->cache->has('name'));
    }

    public function testClearTreatsPrefixLiterally(): void
    {
        $special = $this->createCache(
            driver: $this->driver,
            prefix: 'a*[b]?:',
        );

        $special->set(
            key: 'x',
            value: 1,
        );

        $this->cache->set(
            key: 'ab:x',
            value: 2,
        );

        $special->clear();

        self::assertTrue($this->cache->has('ab:x'));
    }

    public function testNamespaceIsolatesDrivers(): void
    {
        $other = $this->createDriver($this->namespace . 'other:');

        $otherCache = $this->createCache(
            driver: $other,
            prefix: '',
        );

        $otherCache->set(
            key: 'name',
            value: 'Other',
        );

        self::assertNull($this->cache->string('name'));
    }

    public function testValueExpiresAfterTtl(): void
    {
        $this->cache->set(
            key: 'name',
            value: 'Kalle',
            ttl: Duration::fromSeconds(1),
        );

        \sleep(2);

        self::assertNull($this->cache->string('name'));
    }

    public function testIncrementTtlAppliesToCreatedKey(): void
    {
        $this->cache->increment(
            key: 'window',
            ttl: Duration::fromSeconds(1),
        );

        \sleep(2);

        self::assertNull($this->cache->int('window'));
    }
}
