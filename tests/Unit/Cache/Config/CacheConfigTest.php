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

namespace Unit\Cache\Config;

use PHPUnit\Framework\TestCase;
use Support\Cache\InMemoryCacheDriverConfig;
use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Config\CacheConfig;
use Tuxxedo\Cache\Config\CacheScope;

class CacheConfigTest extends TestCase
{
    public function testAcceptsDistinctScopes(): void
    {
        $config = new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
            scopes: [
                new CacheScope(
                    name: 'rateLimit',
                ),
                new CacheScope(
                    name: 'reports',
                ),
            ],
        );

        self::assertCount(2, $config->scopes);
    }

    public function testDefaultsSeparatorToColon(): void
    {
        $config = new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
        );

        self::assertSame(':', $config->separator);
    }

    public function testAcceptsNullSeparator(): void
    {
        $config = new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
            separator: null,
        );

        self::assertNull($config->separator);
    }

    public function testThrowsOnMultiCharacterSeparator(): void
    {
        try {
            new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                separator: '::',
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('separator "::"', $exception->getMessage());
        }
    }

    public function testThrowsOnSeparatorThatCanAppearInScopeName(): void
    {
        self::expectException(CacheException::class);

        new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
            separator: '.',
        );
    }

    public function testThrowsOnWhitespaceSeparator(): void
    {
        self::expectException(CacheException::class);

        new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
            separator: ' ',
        );
    }

    public function testThrowsOnInvalidScopeName(): void
    {
        try {
            new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: [
                    new CacheScope(
                        name: 'rate:limit',
                    ),
                ],
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('scope name "rate:limit"', $exception->getMessage());
        }
    }

    public function testThrowsOnEmptyScopeName(): void
    {
        self::expectException(CacheException::class);

        new CacheConfig(
            default: new InMemoryCacheDriverConfig(),
            scopes: [
                new CacheScope(
                    name: '',
                ),
            ],
        );
    }

    public function testThrowsOnDuplicateScope(): void
    {
        try {
            new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: [
                    new CacheScope(
                        name: 'reports',
                    ),
                    new CacheScope(
                        name: 'reports',
                    ),
                ],
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('declared more than once', $exception->getMessage());
        }
    }

    public function testThrowsOnScopeStartingWithAnotherScope(): void
    {
        try {
            new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: [
                    new CacheScope(
                        name: 'rate',
                    ),
                    new CacheScope(
                        name: 'rateLimit',
                    ),
                ],
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('scope "rateLimit" clashes with scope "rate"', $exception->getMessage());
        }
    }

    public function testThrowsOnScopeThatAnotherScopeStartsWith(): void
    {
        try {
            new CacheConfig(
                default: new InMemoryCacheDriverConfig(),
                scopes: [
                    new CacheScope(
                        name: 'rateLimit',
                    ),
                    new CacheScope(
                        name: 'rate',
                    ),
                ],
            );
        } catch (CacheException $exception) {
            self::assertStringContainsString('scope "rate" clashes with scope "rateLimit"', $exception->getMessage());
        }
    }
}
