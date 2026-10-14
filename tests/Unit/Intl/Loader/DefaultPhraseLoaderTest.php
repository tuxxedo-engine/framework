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

namespace Unit\Intl\Loader;

use PHPUnit\Framework\TestCase;
use Support\Intl\CatalogueScratchpad;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Loader\DefaultPhraseLoader;
use Tuxxedo\Intl\Loader\PhraseLoaderInterface;
use Tuxxedo\Intl\Locale;

class DefaultPhraseLoaderTest extends TestCase
{
    public function testLoadsFromEngineCataloguesWhenHostHasNoneForKey(): void
    {
        $scratchpad = new CatalogueScratchpad();

        $loader = new DefaultPhraseLoader(
            config: new IntlConfig(
                rootDirectory: $scratchpad->root,
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $catalogue = $loader->load(
            locale: Locale::parse('en-US'),
            group: 'validator',
        );

        self::assertIsArray($catalogue);
        self::assertArrayHasKey('common', $catalogue);
    }

    public function testHostCatalogueOverridesEngineCataloguePerKey(): void
    {
        $scratchpad = new CatalogueScratchpad();

        $scratchpad->write(
            localeTag: 'en-US',
            group: 'validator',
            returnExpression: "['common.wrong-type' => 'Host override']",
        );

        $loader = new DefaultPhraseLoader(
            config: new IntlConfig(
                rootDirectory: $scratchpad->root,
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $catalogue = $loader->load(
            locale: Locale::parse('en-US'),
            group: 'validator',
        );

        self::assertIsArray($catalogue);
        self::assertIsArray($catalogue['common']);
        self::assertSame('Host override', $catalogue['common']['wrong-type']);
        self::assertSame('Value at "{path}" may not be null', $catalogue['common']['null-value']);
        self::assertArrayHasKey('email', $catalogue);
    }

    public function testReturnsNullWhenNeitherHostNorEngineHasGroup(): void
    {
        $scratchpad = new CatalogueScratchpad();
        $loader = new DefaultPhraseLoader(
            config: new IntlConfig(
                rootDirectory: $scratchpad->root,
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'nonexistent_group',
            ),
        );
    }

    public function testLoadsEngineCataloguesWithoutHostDirectory(): void
    {
        $loader = new DefaultPhraseLoader(
            config: new IntlConfig(
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $catalogue = $loader->load(
            locale: Locale::parse('fi-FI'),
            group: 'console',
        );

        self::assertIsArray($catalogue);
        self::assertIsArray($catalogue['input']);
        self::assertIsArray($catalogue['input']['confirm']);
        self::assertSame('k,kyllä', $catalogue['input']['confirm']['yesAnswers']);
    }

    public function testContainerResolvesDefaultLoaderWithoutIntlConfiguration(): void
    {
        $catalogue = (new Container())->resolve(PhraseLoaderInterface::class)->load(
            locale: Locale::parse('en-US'),
            group: 'validator',
        );

        self::assertIsArray($catalogue);
        self::assertArrayHasKey('email', $catalogue);
    }
}
