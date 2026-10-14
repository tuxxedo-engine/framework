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

namespace Unit\Intl\Resolver;

use Fixture\Intl\StubLocaleSource;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Resolver\NegotiatingLocaleResolver;
use Tuxxedo\Intl\Source\LocaleSourceRegistry;
use Tuxxedo\Intl\Source\SourcePriority;

class NegotiatingLocaleResolverTest extends TestCase
{
    public function testFallsBackToDefaultWhenNoSourcesRegistered(): void
    {
        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
            sources: new LocaleSourceRegistry(),
        );

        self::assertSame(
            'en-US',
            $resolver->currentLocale()->tag,
        );
    }

    public function testFallsBackToDefaultWhenAllSourcesReturnNull(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::HIGH,
            source: new StubLocaleSource(
                locale: null,
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
            sources: $registry,
        );

        self::assertSame(
            'en-US',
            $resolver->currentLocale()->tag,
        );
    }

    public function testHighestPrioritySourceWins(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: new StubLocaleSource(
                locale: Locale::parse('en-US'),
            ),
        );

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: new StubLocaleSource(
                locale: Locale::parse('da-DK'),
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
                supportedLocales: [
                    Locale::parse('en-US'),
                    Locale::parse('da-DK'),
                ],
            ),
            sources: $registry,
        );

        self::assertSame(
            'da-DK',
            $resolver->currentLocale()->tag,
        );
    }

    public function testAllowListSkipsDisallowedSourceAndMovesToNext(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: new StubLocaleSource(
                locale: Locale::parse('xx-YY'),
            ),
        );

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: new StubLocaleSource(
                locale: Locale::parse('da-DK'),
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
                supportedLocales: [
                    Locale::parse('en-US'),
                    Locale::parse('da-DK'),
                ],
            ),
            sources: $registry,
        );

        self::assertSame(
            'da-DK',
            $resolver->currentLocale()->tag,
        );
    }

    public function testAllowListFallsBackToDefaultWhenNoSourceAllowed(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::HIGHEST,
            source: new StubLocaleSource(
                locale: Locale::parse('xx-YY'),
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
                supportedLocales: [
                    Locale::parse('en-US'),
                ],
            ),
            sources: $registry,
        );

        self::assertSame(
            'en-US',
            $resolver->currentLocale()->tag,
        );
    }

    public function testEmptyAllowListAcceptsEverything(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: new StubLocaleSource(
                locale: Locale::parse('xx-YY'),
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
            sources: $registry,
        );

        self::assertSame(
            'xx-YY',
            $resolver->currentLocale()->tag,
        );
    }

    public function testDefaultLocaleAlwaysPassesAllowList(): void
    {
        $registry = new LocaleSourceRegistry();

        $registry->register(
            priority: SourcePriority::NORMAL,
            source: new StubLocaleSource(
                locale: Locale::parse('en-US'),
            ),
        );

        $resolver = new NegotiatingLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
                supportedLocales: [
                    Locale::parse('da-DK'),
                ],
            ),
            sources: $registry,
        );

        self::assertSame(
            'en-US',
            $resolver->currentLocale()->tag,
        );
    }
}
