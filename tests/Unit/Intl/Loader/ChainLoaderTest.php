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
use Tuxxedo\Intl\Loader\ChainLoader;
use Tuxxedo\Intl\Locale;

class ChainLoaderTest extends TestCase
{
    public function testEmptyChainReturnsNull(): void
    {
        $loader = new ChainLoader();

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testFirstNonNullWins(): void
    {
        $loader = new ChainLoader(
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello from first',
                        ],
                    ],
                ],
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello from second',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            [
                'greeting' => 'Hello from first',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testFallsThroughNullLoaders(): void
    {
        $loader = new ChainLoader(
            new InMemoryPhraseLoader(
                catalogues: [],
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello from second',
                        ],
                    ],
                ],
            ),
        );

        self::assertSame(
            [
                'greeting' => 'Hello from second',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testSourceOfUsesFirstLoaderWithTheGroup(): void
    {
        $loader = new ChainLoader(
            new InMemoryPhraseLoader(
                catalogues: [],
                source: 'empty.php',
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello from second',
                        ],
                    ],
                ],
                source: 'second.php',
            ),
            new InMemoryPhraseLoader(
                catalogues: [
                    'en-US' => [
                        'common' => [
                            'greeting' => 'Hello from third',
                            'farewell' => 'Goodbye from third',
                        ],
                    ],
                ],
                source: 'third.php',
            ),
        );

        $locale = Locale::parse('en-US');

        self::assertSame(
            'second.php',
            $loader->sourceOf(
                locale: $locale,
                group: 'common',
                segments: [
                    'greeting',
                ],
            ),
        );

        self::assertNull(
            $loader->sourceOf(
                locale: $locale,
                group: 'common',
                segments: [
                    'farewell',
                ],
            ),
        );

        self::assertNull(
            $loader->sourceOf(
                locale: $locale,
                group: 'missing',
                segments: [
                    'greeting',
                ],
            ),
        );
    }
}
