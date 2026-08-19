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

namespace Unit\Mail\Signer\Dkim;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Signer\Dkim\BodyCanonicalizer;
use Tuxxedo\Mail\Signer\Dkim\DkimCanonicalization;

class BodyCanonicalizerTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function providesSimpleCases(): \Generator
    {
        yield 'empty body becomes single CRLF' => [
            '',
            "\r\n",
        ];

        yield 'body without trailing CRLF gets one appended' => [
            'hello world',
            "hello world\r\n",
        ];

        yield 'body with single trailing CRLF is preserved' => [
            "hello\r\n",
            "hello\r\n",
        ];

        yield 'multiple trailing CRLFs collapse to one' => [
            "hello\r\n\r\n\r\n",
            "hello\r\n",
        ];

        yield 'body of only CRLFs collapses to one CRLF' => [
            "\r\n\r\n\r\n",
            "\r\n",
        ];

        yield 'inner whitespace and blank lines are preserved verbatim' => [
            "line one\r\n\r\n  line   two\r\n",
            "line one\r\n\r\n  line   two\r\n",
        ];
    }

    #[DataProvider('providesSimpleCases')]
    public function testSimpleCanonicalization(
        string $body,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            BodyCanonicalizer::canonicalize(
                body: $body,
                mode: DkimCanonicalization::SIMPLE,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function providesRelaxedCases(): \Generator
    {
        yield 'empty body becomes single CRLF' => [
            '',
            "\r\n",
        ];

        yield 'runs of spaces collapse to a single space' => [
            'hello   world',
            "hello world\r\n",
        ];

        yield 'tabs collapse and mix with spaces to a single space' => [
            "hello\t\tworld",
            "hello world\r\n",
        ];

        yield 'trailing whitespace per line is stripped' => [
            "hello   \r\nworld  ",
            "hello\r\nworld\r\n",
        ];

        yield 'trailing empty lines are stripped' => [
            "hello\r\n\r\n\r\n",
            "hello\r\n",
        ];

        yield 'multiline body applies both rules together' => [
            "hello  world\r\ntest\t \r\n\r\n",
            "hello world\r\ntest\r\n",
        ];

        yield 'body of only whitespace lines collapses to single CRLF' => [
            "   \r\n\t\t\r\n",
            "\r\n",
        ];
    }

    #[DataProvider('providesRelaxedCases')]
    public function testRelaxedCanonicalization(
        string $body,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            BodyCanonicalizer::canonicalize(
                body: $body,
                mode: DkimCanonicalization::RELAXED,
            ),
        );
    }
}
