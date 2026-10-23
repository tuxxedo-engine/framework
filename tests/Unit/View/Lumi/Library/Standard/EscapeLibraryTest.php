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
use Tuxxedo\View\Lumi\Library\Standard\EscapeLibrary;

class EscapeLibraryTest extends TestCase
{
    private EscapeLibrary $library;

    protected function setUp(): void
    {
        $this->library = new EscapeLibrary();
    }

    public function testEscapeHtmlEscapesTags(): void
    {
        self::assertSame(
            '&lt;b&gt;hello&lt;/b&gt;',
            $this->library->escapeHtml('<b>hello</b>'),
        );
    }

    public function testEscapeHtmlLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->library->escapeHtml('hello'));
    }

    public function testEscapeAttrEscapesQuotes(): void
    {
        self::assertSame(
            '&lt;a href=&quot;test&quot;&gt;',
            $this->library->escapeAttr('<a href="test">'),
        );
    }

    public function testEscapeAttrEscapesSingleQuotes(): void
    {
        self::assertSame('it&#039;s', $this->library->escapeAttr("it's"));
    }

    public function testEscapeCssLeavesAlphanumericUnchanged(): void
    {
        self::assertSame('color', $this->library->escapeCss('color'));
    }

    public function testEscapeCssEscapesSpecialCharacters(): void
    {
        self::assertSame('color\3A red', $this->library->escapeCss('color:red'));
    }

    public function testEscapeJsEscapesSingleQuotes(): void
    {
        self::assertSame("it\\'s", $this->library->escapeJs("it's"));
    }

    public function testEscapeJsLeavesStringWithoutQuotesUnchanged(): void
    {
        self::assertSame('hello', $this->library->escapeJs('hello'));
    }

    public function testEscapeHtmlCommentBreaksDoubleHyphens(): void
    {
        self::assertSame(
            'hello- -world',
            $this->library->escapeHtmlComment('hello--world'),
        );
    }

    public function testEscapeHtmlCommentAppendsSpaceToTrailingHyphen(): void
    {
        self::assertSame('hello- ', $this->library->escapeHtmlComment('hello-'));
    }

    public function testEscapeHtmlCommentLeavesPlainTextUnchanged(): void
    {
        self::assertSame('hello', $this->library->escapeHtmlComment('hello'));
    }

    public function testEscapeHtmlShortCircuitsNonStringValues(): void
    {
        self::assertSame(true, $this->library->escapeHtml(true));
        self::assertSame(42, $this->library->escapeHtml(42));
        self::assertNull($this->library->escapeHtml(null));
    }

    public function testEscapeAttrShortCircuitsNonStringValues(): void
    {
        self::assertSame(false, $this->library->escapeAttr(false));
    }

    public function testEscapeCssShortCircuitsNonStringValues(): void
    {
        self::assertSame(3.14, $this->library->escapeCss(3.14));
    }

    public function testEscapeJsShortCircuitsNonStringValues(): void
    {
        self::assertSame(0, $this->library->escapeJs(0));
    }

    public function testEscapeHtmlCommentShortCircuitsNonStringValues(): void
    {
        self::assertNull($this->library->escapeHtmlComment(null));
    }
}
