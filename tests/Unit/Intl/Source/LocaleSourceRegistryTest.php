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

namespace Unit\Intl\Source;

use Fixture\Intl\StubLocaleSource;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Resolver\NegotiatingLocaleResolver;
use Tuxxedo\Intl\Source\LocaleSourceRegistry;
use Tuxxedo\Intl\Source\LocaleSourceRegistryInterface;
use Tuxxedo\Intl\Source\SourcePriority;

class LocaleSourceRegistryTest extends TestCase
{
    public function testSortedSourcesEmptyWhenNothingRegistered(): void
    {
        $registry = new LocaleSourceRegistry();

        self::assertSame(
            [],
            $registry->sortedSources(),
        );
    }

    public function testSortedSourcesOrderByPriorityHighestFirst(): void
    {
        $normal = new StubLocaleSource(
            locale: Locale::parse('en-US'),
        );

        $highest = new StubLocaleSource(
            locale: Locale::parse('da-DK'),
        );

        $lowest = new StubLocaleSource(
            locale: Locale::parse('fi-FI'),
        );

        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: $normal,
        );

        $registry->register(
            priority: SourcePriority::LOWEST,
            source: $lowest,
        );

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: $highest,
        );

        self::assertSame(
            [
                $highest,
                $normal,
                $lowest,
            ],
            $registry->sortedSources(),
        );
    }

    public function testContainerResolvesOneSharedRegistry(): void
    {
        $container = new Container();

        self::assertSame(
            $container->resolve(LocaleSourceRegistryInterface::class),
            $container->resolve(LocaleSourceRegistryInterface::class),
        );
    }

    public function testSourcesRegisteredThroughContainerReachNegotiatingResolver(): void
    {
        $container = new Container();

        $container->singleton(
            new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $container->resolve(LocaleSourceRegistryInterface::class)->register(
            priority: SourcePriority::HIGHEST,
            source: new StubLocaleSource(
                locale: Locale::parse('da-DK'),
            ),
        );

        self::assertSame(
            'da-DK',
            $container->resolve(NegotiatingLocaleResolver::class)->currentLocale()->tag,
        );
    }

    public function testSourcesRegisteredAfterSortingAreIncluded(): void
    {
        $normal = new StubLocaleSource(
            locale: Locale::parse('en-US'),
        );

        $highest = new StubLocaleSource(
            locale: Locale::parse('da-DK'),
        );

        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: $normal,
        );

        self::assertSame(
            [
                $normal,
            ],
            $registry->sortedSources(),
        );

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: $highest,
        );

        self::assertSame(
            [
                $highest,
                $normal,
            ],
            $registry->sortedSources(),
        );
    }

    public function testRepeatedSortingReturnsTheSameOrder(): void
    {
        $normal = new StubLocaleSource(
            locale: Locale::parse('en-US'),
        );

        $highest = new StubLocaleSource(
            locale: Locale::parse('da-DK'),
        );

        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: $normal,
        );

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: $highest,
        );

        $first = $registry->sortedSources();

        self::assertSame(
            [
                $highest,
                $normal,
            ],
            $first,
        );

        self::assertSame($first, $registry->sortedSources());
    }

    public function testLazySourcesAreCreatedOnFirstSortAndOnlyOnce(): void
    {
        $calls = 0;
        $lazy = new StubLocaleSource(
            locale: Locale::parse('da-DK'),
        );

        $normal = new StubLocaleSource(
            locale: Locale::parse('en-US'),
        );

        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: static function () use (&$calls, $lazy): StubLocaleSource {
                ++$calls;

                return $lazy;
            },
        );

        self::assertSame(0, $calls);

        self::assertSame(
            [
                $lazy,
            ],
            $registry->sortedSources(),
        );

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: $normal,
        );

        self::assertSame(
            [
                $lazy,
                $normal,
            ],
            $registry->sortedSources(),
        );

        self::assertSame(1, $calls);
    }
}
