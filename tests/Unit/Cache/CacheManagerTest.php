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
use Fixture\Cache\Cache\Cache\Profile;
use PHPUnit\Framework\TestCase;
use Support\Cache\InMemoryCacheDriver;
use Support\Cache\InMemoryCacheDriverConfig;
use Tuxxedo\Cache\Cache;
use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\CacheManager;
use Tuxxedo\Cache\Config\CacheConfig;
use Tuxxedo\Cache\Config\CacheScope;
use Tuxxedo\Container\Container;
use Tuxxedo\Mapper\Mapper;

class CacheManagerTest extends TestCase
{
    private CacheManager $manager;

    protected function setUp(): void
    {
        $this->manager = CacheManager::createFromConfig(
            container: new Container(),
            config: new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: [
                    new CacheScope(
                        name: 'reports',
                    ),
                    new CacheScope(
                        name: 'local',
                        driver: new InMemoryCacheDriverConfig(),
                    ),
                ],
            ),
        );
    }

    /**
     * @param list<CacheScope> $scopes
     */
    private function createManager(
        ?string $separator,
        array $scopes,
    ): CacheManager {
        return CacheManager::createFromConfig(
            container: new Container(),
            config: new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: $scopes,
                separator: $separator,
            ),
        );
    }

    public function testKeyWithoutSeparatorRoutesToRoot(): void
    {
        $this->manager->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertSame('Kalle', $this->manager->root->string('name'));
    }

    public function testKeyWithSeparatorRoutesToScope(): void
    {
        $this->manager->set(
            key: 'reports:daily',
            value: 1,
        );

        self::assertSame(1, $this->manager->scope('reports')->int('daily'));
    }

    public function testKeyWithUnknownScopeThrows(): void
    {
        try {
            $this->manager->set(
                key: 'unknown:daily',
                value: 1,
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('scope "unknown" is not declared', $exception->getMessage());
        }
    }

    public function testKeyWithEmptyRemainderThrows(): void
    {
        self::expectException(CacheException::class);

        $this->manager->set(
            key: 'reports:',
            value: 1,
        );
    }

    public function testScopeThrowsOnUnknownName(): void
    {
        self::expectException(CacheException::class);

        $this->manager->scope('unknown');
    }

    public function testScopeReturnsSameInstance(): void
    {
        self::assertSame(
            $this->manager->scope('reports'),
            $this->manager->scope('reports'),
        );
    }

    public function testScopeWithoutDriverSharesRootDriver(): void
    {
        $scope = $this->manager->scope('reports');
        $root = $this->manager->root;

        self::assertInstanceOf(Cache::class, $scope);
        self::assertInstanceOf(Cache::class, $root);
        self::assertSame($root->driver, $scope->driver);
    }

    public function testScopeWithOwnDriverDoesNotShareRootDriver(): void
    {
        $scope = $this->manager->scope('local');
        $root = $this->manager->root;

        self::assertInstanceOf(Cache::class, $scope);
        self::assertInstanceOf(Cache::class, $root);
        self::assertNotSame($root->driver, $scope->driver);
    }

    public function testScopeClearKeepsRootKeys(): void
    {
        $this->manager->set(
            key: 'name',
            value: 'Kalle',
        );

        $this->manager->set(
            key: 'reports:daily',
            value: 1,
        );

        $this->manager->scope('reports')->clear();

        self::assertTrue($this->manager->has('name'));
        self::assertFalse($this->manager->has('reports:daily'));
    }

    public function testClearRemovesRootKeys(): void
    {
        $this->manager->set(
            key: 'name',
            value: 'Kalle',
        );

        $this->manager->clear();

        self::assertFalse($this->manager->has('name'));
    }

    public function testClearRemovesKeysOfScopeOnDefaultDriver(): void
    {
        $this->manager->set(
            key: 'reports:daily',
            value: 1,
        );

        $this->manager->clear();

        self::assertFalse($this->manager->has('reports:daily'));
    }

    public function testClearRemovesKeysOfScopeWithOwnDriver(): void
    {
        $this->manager->set(
            key: 'local:counter',
            value: 1,
        );

        $this->manager->clear();

        self::assertFalse($this->manager->has('local:counter'));
    }

    public function testClearSkipsScopesSharingTheRootDriver(): void
    {
        /** @var Cache $root */
        $root = $this->manager->root;

        /** @var Cache $local */
        $local = $this->manager->scopes['local'];

        /** @var InMemoryCacheDriver $sharedDriver */
        $sharedDriver = $root->driver;

        /** @var InMemoryCacheDriver $localDriver */
        $localDriver = $local->driver;

        $this->manager->clear();

        self::assertSame(
            [
                '',
            ],
            $sharedDriver->clearedPrefixes,
        );

        self::assertSame(
            [
                'local:',
            ],
            $localDriver->clearedPrefixes,
        );
    }

    public function testClearWithoutIsolatedScopesClearsEveryScope(): void
    {
        $driver = new InMemoryCacheDriver();
        $manager = new CacheManager(
            root: new Cache(
                driver: $driver,
                mapper: new Mapper(),
            ),
            scopes: [
                'reports' => new Cache(
                    driver: $driver,
                    mapper: new Mapper(),
                    prefix: 'reports:',
                ),
            ],
        );

        $manager->clear();

        self::assertSame(
            [
                'reports:',
                '',
            ],
            $driver->clearedPrefixes,
        );
    }

    public function testCustomSeparatorRoutesToScope(): void
    {
        $manager = $this->createManager(
            separator: '/',
            scopes: [
                new CacheScope(
                    name: 'reports',
                ),
            ],
        );

        $manager->set(
            key: 'reports/daily',
            value: 1,
        );

        self::assertSame(1, $manager->scope('reports')->int('daily'));
    }

    public function testNullSeparatorRoutesByBarePrefix(): void
    {
        $manager = $this->createManager(
            separator: null,
            scopes: [
                new CacheScope(
                    name: 'rate',
                ),
            ],
        );

        $manager->set(
            key: 'rateLimit',
            value: 1,
        );

        self::assertSame(1, $manager->scope('rate')->int('Limit'));
    }

    public function testNullSeparatorKeepsKeyEqualToScopeNameInRoot(): void
    {
        $manager = $this->createManager(
            separator: null,
            scopes: [
                new CacheScope(
                    name: 'rate',
                ),
            ],
        );

        $manager->set(
            key: 'rate',
            value: 1,
        );

        self::assertSame(1, $manager->root->int('rate'));
    }

    public function testNullSeparatorRoutesNumericScopeName(): void
    {
        $manager = $this->createManager(
            separator: null,
            scopes: [
                new CacheScope(
                    name: '42',
                ),
            ],
        );

        $manager->set(
            key: '42x',
            value: 1,
        );

        self::assertSame(1, $manager->scope('42')->int('x'));
    }

    public function testNullSeparatorKeyWithoutScopePrefixRoutesToRoot(): void
    {
        $manager = $this->createManager(
            separator: null,
            scopes: [
                new CacheScope(
                    name: 'rate',
                ),
            ],
        );

        $manager->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertSame('Kalle', $manager->root->string('name'));
    }

    public function testHasRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'daily',
            value: 1,
        );

        self::assertTrue($this->manager->has('reports:daily'));
    }

    public function testStringRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'title',
            value: 'Daily',
        );

        self::assertSame('Daily', $this->manager->string('reports:title'));
    }

    public function testIntRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'count',
            value: 3,
        );

        self::assertSame(3, $this->manager->int('reports:count'));
    }

    public function testFloatRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'ratio',
            value: 1.5,
        );

        self::assertSame(1.5, $this->manager->float('reports:ratio'));
    }

    public function testBoolRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'enabled',
            value: true,
        );

        self::assertTrue($this->manager->bool('reports:enabled'));
    }

    public function testEnumRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'plan',
            value: Plan::PRO,
        );

        self::assertSame(
            Plan::PRO,
            $this->manager->enum(
                key: 'reports:plan',
                enum: Plan::class,
            ),
        );
    }

    public function testArrayOfStringRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'tags',
            value: [
                'php',
            ],
        );

        self::assertSame(
            [
                'php',
            ],
            $this->manager->arrayOfString('reports:tags'),
        );
    }

    public function testArrayOfIntRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'ids',
            value: [
                1,
            ],
        );

        self::assertSame(
            [
                1,
            ],
            $this->manager->arrayOfInt('reports:ids'),
        );
    }

    public function testArrayOfFloatRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'rates',
            value: [
                1.5,
            ],
        );

        self::assertSame(
            [
                1.5,
            ],
            $this->manager->arrayOfFloat('reports:rates'),
        );
    }

    public function testArrayOfBoolRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'flags',
            value: [
                true,
            ],
        );

        self::assertSame(
            [
                true,
            ],
            $this->manager->arrayOfBool('reports:flags'),
        );
    }

    public function testArrayOfEnumRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'plans',
            value: [
                Plan::FREE,
            ],
        );

        self::assertSame(
            [
                Plan::FREE,
            ],
            $this->manager->arrayOfEnum(
                key: 'reports:plans',
                enum: Plan::class,
            ),
        );
    }

    public function testObjectRoutesToScope(): void
    {
        $money = new Money(
            amount: 100,
            currency: 'DKK',
        );

        $this->manager->set(
            key: 'reports:balance',
            value: $money,
        );

        self::assertSame(
            $money,
            $this->manager->object(
                key: 'reports:balance',
                class: Money::class,
            ),
        );
    }

    public function testMapToRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'profile',
            value: [
                'name' => 'Kalle',
            ],
        );

        $profile = $this->manager->mapTo(
            key: 'reports:profile',
            className: Profile::class,
        );

        self::assertInstanceOf(Profile::class, $profile);
        self::assertSame('Kalle', $profile->name);
    }

    public function testMapToArrayOfRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'profiles',
            value: [
                [
                    'name' => 'Kalle',
                ],
            ],
        );

        $profiles = $this->manager->mapToArrayOf(
            key: 'reports:profiles',
            className: Profile::class,
        );

        self::assertCount(1, $profiles ?? []);
    }

    public function testAddRoutesToScope(): void
    {
        (void) $this->manager->add(
            key: 'reports:lock',
            value: 'first',
        );

        self::assertSame('first', $this->manager->scope('reports')->string('lock'));
    }

    public function testRememberRoutesToScope(): void
    {
        $this->manager->remember(
            key: 'reports:value',
            factory: static fn (): string => 'computed',
        );

        self::assertSame('computed', $this->manager->scope('reports')->string('value'));
    }

    public function testIncrementRoutesToScope(): void
    {
        $this->manager->increment('local:visits');

        self::assertSame(1, $this->manager->scope('local')->int('visits'));
    }

    public function testDecrementRoutesToScope(): void
    {
        $this->manager->decrement('local:stock');

        self::assertSame(-1, $this->manager->scope('local')->int('stock'));
    }

    public function testDeleteRoutesToScope(): void
    {
        $this->manager->scope('reports')->set(
            key: 'daily',
            value: 1,
        );

        $this->manager->delete('reports:daily');

        self::assertFalse($this->manager->scope('reports')->has('daily'));
    }
}
