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

namespace Unit\Intl\Config;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;

class IntlConfigTest extends TestCase
{
    private const string HOST_CONFIG = __DIR__ . '/../../../Fixture/Intl/Config/intl.php';

    public function testConstructorExposesFields(): void
    {
        $config = new IntlConfig(
            rootDirectory: '/tmp/language',
            defaultLocale: Locale::parse('en-US'),
            supportedLocales: [
                Locale::parse('en-US'),
                Locale::parse('da-DK'),
            ],
            missingKeyMode: MissingKeyMode::STRICT,
        );

        self::assertSame('/tmp/language', $config->rootDirectory);
        self::assertSame('en-US', $config->defaultLocale->tag);
        self::assertCount(2, $config->supportedLocales);
        self::assertSame(MissingKeyMode::STRICT, $config->missingKeyMode);
    }

    public function testSupportedLocalesDefaultsToEmptyList(): void
    {
        $config = new IntlConfig(
            rootDirectory: '/tmp/language',
            defaultLocale: Locale::parse('en-US'),
        );

        self::assertSame([], $config->supportedLocales);
    }

    public function testMissingKeyModeDefaultsToPassthrough(): void
    {
        $config = new IntlConfig(
            rootDirectory: '/tmp/language',
            defaultLocale: Locale::parse('en-US'),
        );

        self::assertSame(MissingKeyMode::PASSTHROUGH, $config->missingKeyMode);
    }

    public function testRootDirectoryDefaultsToNull(): void
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
        );

        self::assertNull($config->rootDirectory);
    }

    public function testContainerFallsBackToEnglishDefault(): void
    {
        $container = new Container();
        $config = $container->resolve(IntlConfigInterface::class);

        self::assertSame('en-US', $config->defaultLocale->tag);
        self::assertNull($config->rootDirectory);
        self::assertSame([], $config->supportedLocales);
        self::assertSame(MissingKeyMode::PASSTHROUGH, $config->missingKeyMode);
        self::assertSame($config, $container->resolve(IntlConfigInterface::class));
    }

    public function testHostConfigFileTakesPrecedenceOverDefault(): void
    {
        $container = new Container();

        Config::createFromFile($container, self::HOST_CONFIG);

        self::assertSame(
            'da-DK',
            $container->resolve(IntlConfigInterface::class)->defaultLocale->tag,
        );
    }
}
