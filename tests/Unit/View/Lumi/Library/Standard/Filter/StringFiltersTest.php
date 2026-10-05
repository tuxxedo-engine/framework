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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Filter\StringFilters;

class StringFiltersTest extends TestCase
{
    private StringFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new StringFilters();
    }

    public function testCapitalizeTitlecasesEachWord(): void
    {
        self::assertSame('Hello World', $this->filters->capitalize('hello world'));
    }

    public function testCapitalizeHandlesSingleWord(): void
    {
        self::assertSame('Hello', $this->filters->capitalize('hello'));
    }

    public function testLcfirstLowercasesFirstCharacter(): void
    {
        self::assertSame('hELLO', $this->filters->lcfirst('HELLO'));
    }

    public function testLcfirstHandlesUnicode(): void
    {
        self::assertSame('éLLO', $this->filters->lcfirst('ÉLLO'));
    }

    public function testLowerLowercasesString(): void
    {
        self::assertSame('hello', $this->filters->lower('HELLO'));
    }

    public function testLowerHandlesUnicode(): void
    {
        self::assertSame('héllo', $this->filters->lower('HÉLLO'));
    }

    public function testUpperUppercasesString(): void
    {
        self::assertSame('HELLO', $this->filters->upper('hello'));
    }

    public function testUpperHandlesUnicode(): void
    {
        self::assertSame('HÉLLO', $this->filters->upper('héllo'));
    }

    public function testLtrimTrimsLeftSideOnly(): void
    {
        self::assertSame('hello  ', $this->filters->ltrim('  hello  '));
    }

    public function testRtrimTrimsRightSideOnly(): void
    {
        self::assertSame('  hello', $this->filters->rtrim('  hello  '));
    }

    public function testTrimTrimsBothSides(): void
    {
        self::assertSame('hello', $this->filters->trim('  hello  '));
    }

    public function testTrimLeavesInnerWhitespaceIntact(): void
    {
        self::assertSame('hello world', $this->filters->trim('  hello world  '));
    }

    public function testSlugifySlugifiesSimpleString(): void
    {
        self::assertSame('hello-world', $this->filters->slugify('Hello World'));
    }

    public function testSlugifyReplacesSpecialCharacters(): void
    {
        self::assertSame('hello-world', $this->filters->slugify('Hello, World'));
    }

    public function testSlugifyLowercasesResult(): void
    {
        self::assertSame('foo-bar', $this->filters->slugify('FOO BAR'));
    }

    public function testSlugifyPreservesUnicodeLetters(): void
    {
        self::assertSame('héllo', $this->filters->slugify('Héllo'));
    }

    public function testStripTagsStripsHtmlTags(): void
    {
        self::assertSame(
            'hello world',
            $this->filters->stripTags('<p>hello <strong>world</strong></p>'),
        );
    }

    public function testStripTagsLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->filters->stripTags('hello'));
    }

    public function testNl2brInsertsBreakBeforeNewline(): void
    {
        self::assertSame("hello<br>\nworld", $this->filters->nl2br("hello\nworld"));
    }

    public function testNl2brLeavesStringWithoutNewlinesUnchanged(): void
    {
        self::assertSame('hello world', $this->filters->nl2br('hello world'));
    }
}
