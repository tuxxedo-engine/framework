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

namespace Unit\Database\Query\FullText;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\FullText\FullText;

class FullTextTest extends TestCase
{
    public function testNormalizeFoldsAccentsCaseAndSeparators(): void
    {
        self::assertSame(
            'tabak mix l moine f line',
            FullText::normalize("Tabák MIX — L'moine / F-Line"),
        );
    }

    public function testNormalizeReturnsEmptyStringWithoutWords(): void
    {
        self::assertSame(
            '',
            FullText::normalize(' -- !! '),
        );
    }

    public function testTermsDropTermsOutsideLengthBounds(): void
    {
        self::assertSame(
            [
                'mint',
            ],
            FullText::terms('mi mint ' . \str_repeat('a', FullText::MAX_TERM_LENGTH + 1)),
        );
    }

    public function testTermsKeepTermsAtLengthBounds(): void
    {
        $longest = \str_repeat('a', FullText::MAX_TERM_LENGTH);

        self::assertSame(
            [
                'ice',
                $longest,
            ],
            FullText::terms('ice ' . $longest),
        );
    }

    public function testTermsAreDeduplicated(): void
    {
        self::assertSame(
            [
                'mint',
                'ice',
            ],
            FullText::terms('Mint ice MINT'),
        );
    }

    public function testTermsNeverContainOperatorCharacters(): void
    {
        self::assertSame(
            [
                'line',
                'cane',
            ],
            FullText::terms('+f-line* "cane" @:&'),
        );
    }

    public function testIndexNameJoinsTableAndColumns(): void
    {
        self::assertSame(
            'articles_title_summary_ft',
            FullText::indexName(
                table: 'articles',
                columns: [
                    'title',
                    'summary',
                ],
            ),
        );
    }
}
