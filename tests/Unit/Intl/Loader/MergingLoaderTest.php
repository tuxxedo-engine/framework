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

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Loader\MergingLoader;
use Tuxxedo\Intl\Locale;

class MergingLoaderTest extends TestCase
{
    public function testEmptyLoaderReturnsNull(): void
    {
        self::assertNull(
            (new MergingLoader())->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testReturnsNullWhenNoLoaderHasGroup(): void
    {
        $loader = new MergingLoader(
            new InMemoryPhraseLoader(
                catalogues: [],
            ),
            new InMemoryPhraseLoader(
                catalogues: [],
            ),
        );

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testEarlierLoaderOverridesPerKey(): void
    {
        $loader = new MergingLoader(
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Host hello',
                            'items' => [
                                'one' => 'Host {count} item',
                            ],
                        ],
                    ],
                ],
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Engine hello',
                            'farewell' => 'Engine goodbye',
                            'items' => [
                                'one' => '{count} item',
                                'other' => '{count} items',
                            ],
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            [
                'greeting' => 'Host hello',
                'farewell' => 'Engine goodbye',
                'items' => [
                    'one' => 'Host {count} item',
                    'other' => '{count} items',
                ],
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testUsesWhicheverLoaderHasTheGroup(): void
    {
        $loader = new MergingLoader(
            new InMemoryPhraseLoader(
                catalogues: [],
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Engine hello',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            [
                'greeting' => 'Engine hello',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }
}
