<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Escaper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Escaper\Escaper;

class EscaperTest extends TestCase
{
    public static function htmlDataProvider(): \Generator
    {
        yield [
            '&',
            '&amp;',
        ];

        yield [
            '<div>',
            '&lt;div&gt;',
        ];

        yield [
            '"a&b"',
            '&quot;a&amp;b&quot;',
        ];

        yield [
            "Bob's & <b>",
            "Bob&#039;s &amp; &lt;b&gt;",
        ];

        yield [
            '5 > 3 & 2 < 4',
            '5 &gt; 3 &amp; 2 &lt; 4',
        ];
    }

    #[DataProvider('htmlDataProvider')]
    public function testHtml(string $value, string $expected): void
    {
        self::assertSame((new Escaper())->html($value), $expected);
    }

    public static function attributeDataProvider(): \Generator
    {
        yield [
            'O\'Reilly & "Friends"',
            'O&#039;Reilly &amp; &quot;Friends&quot;',
        ];

        yield [
            '<input value="x">',
            '&lt;input value=&quot;x&quot;&gt;',
        ];

        yield [
            '5 > 3 & 2 < 4',
            '5 &gt; 3 &amp; 2 &lt; 4',
        ];

        yield [
            "''\"\"",
            '&#039;&#039;&quot;&quot;',
        ];

        yield [
            '😀 & <',
            '😀 &amp; &lt;',
        ];
    }

    #[DataProvider('attributeDataProvider')]
    public function testAttribute(string $value, string $expected): void
    {
        self::assertSame((new Escaper())->attribute($value), $expected);
    }

    public static function jsDataProvider(): \Generator
    {
        yield [
            "O'Reilly",
            "O\'Reilly",
        ];

        yield [
            "rock 'n' roll",
            "rock \'n\' roll",
        ];

        yield [
            "''",
            "\'\'",
        ];

        yield [
            "she said: 'hi'",
            "she said: \'hi\'",
        ];

        yield [
            "no quotes",
            "no quotes",
        ];
    }

    #[DataProvider('jsDataProvider')]
    public function testJs(string $value, string $expected): void
    {
        self::assertSame((new Escaper())->js($value), $expected);
    }

    public static function urlDataProvider(): \Generator
    {
        yield [
            'hello world',
            'hello%20world',
        ];

        yield [
            'a/b?c=d&e=f',
            'a%2Fb%3Fc%3Dd%26e%3Df',
        ];

        yield [
            'Café 🍩',
            'Caf%C3%A9%20%F0%9F%8D%A9',
        ];

        yield [
            '100% sure',
            '100%25%20sure',
        ];

        yield [
            'こんにちは',
            '%E3%81%93%E3%82%93%E3%81%AB%E3%81%A1%E3%81%AF',
        ];
    }

    #[DataProvider('urlDataProvider')]
    public function testUrl(string $value, string $expected): void
    {
        self::assertSame((new Escaper())->url($value), $expected);
    }
}
