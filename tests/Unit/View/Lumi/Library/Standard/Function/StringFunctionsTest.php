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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Function\StringFunctions;

class StringFunctionsTest extends TestCase
{
    private StringFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new StringFunctions();
    }

    public function testJoinJoinsArrayWithSeparator(): void
    {
        self::assertSame(
            'foo|bar|baz',
            $this->functions->join(
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
            $this->functions->split(
                value: 'foo|bar|baz',
                separator: '|',
            ),
        );
    }

    public function testRepeatRepeatsString(): void
    {
        self::assertSame('abcabcabc', $this->functions->repeat('abc', 3));
    }

    public function testRepeatOnce(): void
    {
        self::assertSame('abc', $this->functions->repeat('abc', 1));
    }

    public function testReplaceReplacesSubstring(): void
    {
        self::assertSame(
            'hello world',
            $this->functions->replace(
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
            $this->functions->replace(
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
            $this->functions->replace(
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
        self::assertSame('hello', $this->functions->truncate('hello world', 5));
    }

    public function testTruncateReturnsFullStringWhenShorter(): void
    {
        self::assertSame('hi', $this->functions->truncate('hi', 10));
    }

    public function testTruncateRespectsMbStringBoundaries(): void
    {
        self::assertSame('こんに', $this->functions->truncate('こんにちは', 3));
    }

    public function testPadPadsOnBothSides(): void
    {
        self::assertSame('  hi  ', $this->functions->pad('hi', 6));
    }

    public function testPadUsesCustomPadString(): void
    {
        self::assertSame('--hi--', $this->functions->pad('hi', 6, '-'));
    }

    public function testLeftPadPadsOnLeft(): void
    {
        self::assertSame('   hi', $this->functions->leftPad('hi', 5));
    }

    public function testLeftPadUsesCustomPadString(): void
    {
        self::assertSame('***hi', $this->functions->leftPad('hi', 5, '*'));
    }

    public function testRightPadPadsOnRight(): void
    {
        self::assertSame('hi   ', $this->functions->rightPad('hi', 5));
    }

    public function testRightPadUsesCustomPadString(): void
    {
        self::assertSame('hi***', $this->functions->rightPad('hi', 5, '*'));
    }

    public function testNumberFormatsWithDefaults(): void
    {
        self::assertSame('1,234', $this->functions->number(1234));
    }

    public function testNumberFormatsWithDecimals(): void
    {
        self::assertSame('1,234,567.89', $this->functions->number(1234567.89, 2));
    }

    public function testNumberFormatsWithCustomSeparators(): void
    {
        self::assertSame(
            '1.234.567,89',
            $this->functions->number(
                number: 1234567.89,
                decimals: 2,
                decimalSeparator: ',',
                thousandsSeparator: '.',
            ),
        );
    }

    public function testNumberFormatsWithExplicitDecimals(): void
    {
        self::assertSame('42.00', $this->functions->number(42, 2));
    }
}
