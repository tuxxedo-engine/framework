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
use Tuxxedo\Intl\Loader\FlatFileLoader;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;

class FlatFileLoaderTest extends TestCase
{
    private CatalogueScratchpad $scratchpad;

    protected function setUp(): void
    {
        $this->scratchpad = new CatalogueScratchpad();
    }

    protected function tearDown(): void
    {
        $this->scratchpad->cleanup();
    }

    public function testLoadReturnsNullWhenFileMissing(): void
    {
        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertNull(
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'validation',
            ),
        );
    }

    public function testLoadReturnsNestedArrayAsIs(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error' => ['mail' => ['noSubject' => 'No subject']]]",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertSame(
            [
                'error' => [
                    'mail' => [
                        'noSubject' => 'No subject',
                    ],
                ],
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testLoadNormalisesFlatDottedKeyIntoNestedTree(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error.mail.noSubject' => 'No subject']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertSame(
            [
                'error' => [
                    'mail' => [
                        'noSubject' => 'No subject',
                    ],
                ],
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testLoadMergesFlatAndNestedWhenCompatible(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error' => ['mail' => ['subject' => 'A']], 'error.mail.body' => 'B']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertSame(
            [
                'error' => [
                    'mail' => [
                        'subject' => 'A',
                        'body' => 'B',
                    ],
                ],
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testLoadRejectsConflictingFlatAndNested(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error' => ['mail' => 'A'], 'error.mail' => 'B']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadRejectsBranchVersusLeafCollision(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error' => 'leaf', 'error.mail' => 'B']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadRejectsMalformedCatalogue(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "'not an array'",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadRejectsNonStringTopLevelKey(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "[0 => 'leaf']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadRejectsNonStringNestedKey(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error' => [0 => 'leaf']]",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadMergesTopLevelArrayValueIntoExistingNestedBranch(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['a.b' => ['x' => 'A'], 'a' => ['b' => ['y' => 'B']]]",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertSame(
            [
                'a' => [
                    'b' => [
                        'x' => 'A',
                        'y' => 'B',
                    ],
                ],
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'common',
            ),
        );
    }

    public function testLoadMergeSegmentDetectsScalarCollisionAgainstExistingValue(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['a.b' => 'A', 'a' => ['b' => 'B']]",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadRejectsNonStringLeafValues(): void
    {
        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        $cases = [
            'int' => "['bad' => 5]",
            'float' => "['bad' => 1.5]",
            'bool' => "['bad' => true]",
            'null' => "['bad' => null]",
            'object' => "['bad' => new \\stdClass()]",
            'nested' => "['ok' => ['bad' => 5]]",
            'dotted' => "['a.bad' => false]",
        ];

        foreach ($cases as $group => $returnExpression) {
            $this->scratchpad->write(
                localeTag: 'en-US',
                group: $group,
                returnExpression: $returnExpression,
            );

            try {
                $loader->load(
                    locale: Locale::parse('en-US'),
                    group: $group,
                );

                self::fail(\sprintf('Expected a non-string %s leaf to be rejected', $group));
            } catch (TranslationException $exception) {
                self::assertStringContainsString('non-string value at key "', $exception->getMessage());
            }
        }
    }

    public function testLoadRejectsEmptyDottedSegment(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['error..mail' => 'X']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::expectException(TranslationException::class);

        $loader->load(
            locale: Locale::parse('en-US'),
            group: 'common',
        );
    }

    public function testLoadAcceptsGroupNamesWithDashAndUnderscore(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'my-plugin_notes',
            returnExpression: "['title' => 'Notes']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        self::assertSame(
            [
                'title' => 'Notes',
            ],
            $loader->load(
                locale: Locale::parse('en-US'),
                group: 'my-plugin_notes',
            ),
        );
    }

    public function testLoadReturnsNullForGroupNamesOutsideAllowedCharacters(): void
    {
        $this->scratchpad->write(
            localeTag: 'en-US',
            group: 'common',
            returnExpression: "['greeting' => 'Hello']",
        );

        $loader = new FlatFileLoader(
            rootDirectory: $this->scratchpad->root,
        );

        foreach (['', '../en-US/common', 'en-US/../common', 'common.php', 'com mon', 'common\\x'] as $group) {
            self::assertNull(
                $loader->load(
                    locale: Locale::parse('en-US'),
                    group: $group,
                ),
                \sprintf('Group "%s" should not resolve to a catalogue file', $group),
            );
        }
    }
}
