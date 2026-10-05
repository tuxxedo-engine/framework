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
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeFilters;

class EscapeFiltersTest extends TestCase
{
    private EscapeFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new EscapeFilters();
    }

    public function testEscapeHtmlEscapesTags(): void
    {
        self::assertSame(
            '&lt;b&gt;hello&lt;/b&gt;',
            $this->filters->escapeHtml('<b>hello</b>'),
        );
    }

    public function testEscapeHtmlLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->filters->escapeHtml('hello'));
    }

    public function testEscapeAttrEscapesQuotes(): void
    {
        self::assertSame(
            '&lt;a href=&quot;test&quot;&gt;',
            $this->filters->escapeAttr('<a href="test">'),
        );
    }

    public function testEscapeAttrEscapesSingleQuotes(): void
    {
        self::assertSame('it&#039;s', $this->filters->escapeAttr("it's"));
    }

    public function testEscapeCssLeavesAlphanumericUnchanged(): void
    {
        self::assertSame('color', $this->filters->escapeCss('color'));
    }

    public function testEscapeCssEscapesSpecialCharacters(): void
    {
        self::assertSame('color\3A red', $this->filters->escapeCss('color:red'));
    }

    public function testEscapeJsEscapesSingleQuotes(): void
    {
        self::assertSame("it\\'s", $this->filters->escapeJs("it's"));
    }

    public function testEscapeJsLeavesStringWithoutQuotesUnchanged(): void
    {
        self::assertSame('hello', $this->filters->escapeJs('hello'));
    }

    public function testEscapeHtmlCommentBreaksDoubleHyphens(): void
    {
        self::assertSame(
            'hello- -world',
            $this->filters->escapeHtmlComment('hello--world'),
        );
    }

    public function testEscapeHtmlCommentAppendsSpaceToTrailingHyphen(): void
    {
        self::assertSame('hello- ', $this->filters->escapeHtmlComment('hello-'));
    }

    public function testEscapeHtmlCommentLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->filters->escapeHtmlComment('hello'));
    }

    public function testEscapeHtmlShortCircuitsNonStringValues(): void
    {
        self::assertSame(true, $this->filters->escapeHtml(true));
        self::assertSame(42, $this->filters->escapeHtml(42));
        self::assertNull($this->filters->escapeHtml(null));
    }

    public function testEscapeAttrShortCircuitsNonStringValues(): void
    {
        self::assertSame(false, $this->filters->escapeAttr(false));
    }

    public function testEscapeCssShortCircuitsNonStringValues(): void
    {
        self::assertSame(3.14, $this->filters->escapeCss(3.14));
    }

    public function testEscapeJsShortCircuitsNonStringValues(): void
    {
        self::assertSame(0, $this->filters->escapeJs(0));
    }

    public function testEscapeHtmlCommentShortCircuitsNonStringValues(): void
    {
        self::assertNull($this->filters->escapeHtmlComment(null));
    }
}
