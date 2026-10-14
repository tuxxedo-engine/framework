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

use Fixture\Intl\CountingPhraseLoader;
use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Loader\CachingLoader;
use Tuxxedo\Intl\Locale;

class CachingLoaderTest extends TestCase
{
    public function testLoadsEachLocaleAndGroupOnce(): void
    {
        $inner = new CountingPhraseLoader(
            loader: new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                    ],
                ],
            ),
        );

        $loader = new CachingLoader(
            loader: $inner,
        );

        for ($i = 0; $i < 3; ++$i) {
            self::assertSame(
                [
                    'greeting' => 'Hello',
                ],
                $loader->load(
                    locale: Locale::parse('en-US'),
                    group: 'common',
                ),
            );
        }

        self::assertSame(1, $inner->calls);
    }

    public function testCachesMissingCatalogues(): void
    {
        $inner = new CountingPhraseLoader(
            loader: new InMemoryPhraseLoader(
                catalogues: [],
            ),
        );

        $loader = new CachingLoader(
            loader: $inner,
        );

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );

        self::assertSame(1, $inner->calls);
    }

    public function testKeepsLocalesAndGroupsApart(): void
    {
        $inner = new CountingPhraseLoader(
            loader: new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello',
                        ],
                        'mail' => [
                            'subject' => 'Welcome',
                        ],
                    ],
                    'da-DK' => [
                        'common' => [
                            'greeting' => 'Hej',
                        ],
                    ],
                ],
            ),
        );

        $loader = new CachingLoader(
            loader: $inner,
        );

        self::assertSame(
            [
                'greeting' => 'Hello',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );

        self::assertSame(
            [
                'subject' => 'Welcome',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'mail',
            ),
        );

        self::assertSame(
            [
                'greeting' => 'Hej',
            ],
            $loader->load(
                locale: Locale::parse('da-DK'),
                group: 'common',
            ),
        );

        self::assertSame(3, $inner->calls);
    }
}
