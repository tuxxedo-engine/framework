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

namespace Unit\View\Lumi\Library\Standard;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\StringLibrary;

class StringLibraryTest extends TestCase
{
    private StringLibrary $library;

    protected function setUp(): void
    {
        $this->library = new StringLibrary();
    }

    public function testJoinJoinsArrayWithSeparator(): void
    {
        self::assertSame(
            'foo|bar|baz',
            $this->library->join(
                value: [
                    'foo',
                    'bar',
                    'baz',
                ],
                separator: '|',
            ),
        );
    }

    public function testSplitSplitsStringBySeparator(): void
    {
        self::assertSame(
            [
                'foo',
                'bar',
                'baz',
            ],
            $this->library->split(
                value: 'foo|bar|baz',
                separator: '|',
            ),
        );
    }

    public function testRepeatRepeatsString(): void
    {
        self::assertSame('abcabcabc', $this->library->repeat('abc', 3));
    }

    public function testRepeatOnce(): void
    {
        self::assertSame('abc', $this->library->repeat('abc', 1));
    }

    public function testReplaceReplacesSubstring(): void
    {
        self::assertSame(
            'hello world',
            $this->library->replace(
                subject: 'hello earth',
                search: 'earth',
                replace: 'world',
            ),
        );
    }

    public function testReplaceReplacesMultipleOccurrences(): void
    {
        self::assertSame(
            'b b b',
            $this->library->replace(
                subject: 'a a a',
                search: 'a',
                replace: 'b',
            ),
        );
    }

    public function testReplaceReplacesArrayOfSearchTerms(): void
    {
        self::assertSame(
            'x x',
            $this->library->replace(
                subject: 'a b',
                search: [
                    'a',
                    'b',
                ],
                replace: 'x',
            ),
        );
    }

    public function testTruncateTruncatesStringToPosition(): void
    {
        self::assertSame('hello', $this->library->truncate('hello world', 5));
    }

    public function testTruncateReturnsFullStringWhenShorter(): void
    {
        self::assertSame('hi', $this->library->truncate('hi', 10));
    }

    public function testTruncateRespectsMbStringBoundaries(): void
    {
        self::assertSame('こんに', $this->library->truncate('こんにちは', 3));
    }

    public function testTruncateKeepsGraphemeClustersWhole(): void
    {
        self::assertSame("A\u{030A}", $this->library->truncate("A\u{030A}se", 1));
        self::assertSame("\u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467}", $this->library->truncate("\u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467} hi", 1));
        self::assertSame("\u{1F44D}\u{1F3FD}", $this->library->truncate("\u{1F44D}\u{1F3FD}ok", 1));
    }

    public function testTruncateKeepsInvalidUtf8Intact(): void
    {
        self::assertSame("\xFFab", $this->library->truncate("\xFFab", 3));
    }

    public function testPadPadsOnBothSides(): void
    {
        self::assertSame('  hi  ', $this->library->pad('hi', 6));
    }

    public function testPadUsesCustomPadString(): void
    {
        self::assertSame('--hi--', $this->library->pad('hi', 6, '-'));
    }

    public function testLeftPadPadsOnLeft(): void
    {
        self::assertSame('   hi', $this->library->leftPad('hi', 5));
    }

    public function testLeftPadUsesCustomPadString(): void
    {
        self::assertSame('***hi', $this->library->leftPad('hi', 5, '*'));
    }

    public function testRightPadPadsOnRight(): void
    {
        self::assertSame('hi   ', $this->library->rightPad('hi', 5));
    }

    public function testRightPadUsesCustomPadString(): void
    {
        self::assertSame('hi***', $this->library->rightPad('hi', 5, '*'));
    }

    public function testPadPutsTheExtraPaddingOnTheRight(): void
    {
        self::assertSame('-hi--', $this->library->pad('hi', 5, '-'));
    }

    public function testPadCountsGraphemes(): void
    {
        self::assertSame("A\u{030A}se..", $this->library->rightPad("A\u{030A}se", 5, '.'));
        self::assertSame("a\u{1F44D}\u{1F3FD}\u{1F44D}\u{1F3FD}", $this->library->rightPad('a', 3, "\u{1F44D}\u{1F3FD}"));
    }

    public function testPadCyclesMultiGraphemePadString(): void
    {
        self::assertSame('abahi', $this->library->leftPad('hi', 5, 'ab'));
    }

    public function testPadReturnsStringUnchangedWhenLongEnoughOrPadStringEmpty(): void
    {
        self::assertSame('hello', $this->library->rightPad('hello', 3));
        self::assertSame('hi', $this->library->pad('hi', 6, ''));
    }

    public function testNumberFormatsWithDefaults(): void
    {
        self::assertSame('1,234', $this->library->number(1234));
    }

    public function testNumberFormatsWithDecimals(): void
    {
        self::assertSame('1,234,567.89', $this->library->number(1234567.89, 2));
    }

    public function testNumberFormatsWithCustomSeparators(): void
    {
        self::assertSame(
            '1.234.567,89',
            $this->library->number(
                number: 1234567.89,
                decimals: 2,
                decimalSeparator: ',',
                thousandsSeparator: '.',
            ),
        );
    }

    public function testNumberFormatsWithExplicitDecimals(): void
    {
        self::assertSame('42.00', $this->library->number(42, 2));
    }

    public function testCapitalizeTitlecasesEachWord(): void
    {
        self::assertSame('Hello World', $this->library->capitalize('hello world'));
    }

    public function testCapitalizeHandlesSingleWord(): void
    {
        self::assertSame('Hello', $this->library->capitalize('hello'));
    }

    public function testLcfirstLowercasesFirstCharacter(): void
    {
        self::assertSame('hELLO', $this->library->lcfirst('HELLO'));
    }

    public function testLcfirstHandlesUnicode(): void
    {
        self::assertSame('éLLO', $this->library->lcfirst('ÉLLO'));
    }

    public function testLowerLowercasesString(): void
    {
        self::assertSame('hello', $this->library->lower('HELLO'));
    }

    public function testLowerHandlesUnicode(): void
    {
        self::assertSame('héllo', $this->library->lower('HÉLLO'));
    }

    public function testUpperUppercasesString(): void
    {
        self::assertSame('HELLO', $this->library->upper('hello'));
    }

    public function testUpperHandlesUnicode(): void
    {
        self::assertSame('HÉLLO', $this->library->upper('héllo'));
    }

    public function testLtrimTrimsLeftSideOnly(): void
    {
        self::assertSame('hello  ', $this->library->ltrim('  hello  '));
    }

    public function testRtrimTrimsRightSideOnly(): void
    {
        self::assertSame('  hello', $this->library->rtrim('  hello  '));
    }

    public function testTrimTrimsBothSides(): void
    {
        self::assertSame('hello', $this->library->trim('  hello  '));
    }

    public function testTrimLeavesInnerWhitespaceIntact(): void
    {
        self::assertSame('hello world', $this->library->trim('  hello world  '));
    }

    public function testSlugifySlugifiesSimpleString(): void
    {
        self::assertSame('hello-world', $this->library->slugify('Hello World'));
    }

    public function testSlugifyReplacesSpecialCharacters(): void
    {
        self::assertSame('hello-world', $this->library->slugify('Hello, World'));
    }

    public function testSlugifyLowercasesResult(): void
    {
        self::assertSame('foo-bar', $this->library->slugify('FOO BAR'));
    }

    public function testSlugifyPreservesUnicodeLetters(): void
    {
        self::assertSame('héllo', $this->library->slugify('Héllo'));
    }

    public function testStripTagsStripsHtmlTags(): void
    {
        self::assertSame(
            'hello world',
            $this->library->stripTags('<p>hello <strong>world</strong></p>'),
        );
    }

    public function testStripTagsLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->library->stripTags('hello'));
    }

    public function testNl2brInsertsBreakBeforeNewline(): void
    {
        self::assertSame("hello<br>\nworld", $this->library->nl2br("hello\nworld"));
    }

    public function testNl2brLeavesStringWithoutNewlinesUnchanged(): void
    {
        self::assertSame('hello world', $this->library->nl2br('hello world'));
    }
}
