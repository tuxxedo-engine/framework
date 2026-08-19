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
use Tuxxedo\Mail\Signer\Dkim\DkimCanonicalization;
use Tuxxedo\Mail\Signer\Dkim\HeaderCanonicalizer;

class HeaderCanonicalizerTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function providesSimpleCases(): \Generator
    {
        yield 'header passes through unchanged' => [
            'From: Test User <test@example.com>',
            'From: Test User <test@example.com>',
        ];

        yield 'folded header retains folding' => [
            "Subject: hello\r\n there",
            "Subject: hello\r\n there",
        ];

        yield 'unusual casing preserved verbatim' => [
            'X-Custom-Header: MixedCase Value',
            'X-Custom-Header: MixedCase Value',
        ];

        yield 'input without colon is returned as-is' => [
            'no-colon-here',
            'no-colon-here',
        ];
    }

    #[DataProvider('providesSimpleCases')]
    public function testSimpleCanonicalization(
        string $header,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            HeaderCanonicalizer::canonicalize(
                header: $header,
                mode: DkimCanonicalization::SIMPLE,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function providesRelaxedCases(): \Generator
    {
        yield 'name lowercased and value trimmed' => [
            'Subject: hello there',
            'subject:hello there',
        ];

        yield 'inner runs of whitespace collapse to single space' => [
            'Subject: hello   there  ',
            'subject:hello there',
        ];

        yield 'tabs in value are treated as whitespace and collapsed' => [
            "Subject:\thello\tworld",
            'subject:hello world',
        ];

        yield 'folded continuation lines are unfolded before processing' => [
            "Subject: hello\r\n there",
            'subject:hello there',
        ];

        yield 'trailing whitespace before colon on the name is stripped' => [
            'Subject   : hello',
            'subject:hello',
        ];

        yield 'no colon: whole input is lowercased and right-trimmed' => [
            'Just-A-Header-Name  ',
            'just-a-header-name',
        ];

        yield 'no colon with folded line: unfolded then lowercased' => [
            "Standalone\r\n Continuation",
            'standalone continuation',
        ];
    }

    #[DataProvider('providesRelaxedCases')]
    public function testRelaxedCanonicalization(
        string $header,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            HeaderCanonicalizer::canonicalize(
                header: $header,
                mode: DkimCanonicalization::RELAXED,
            ),
        );
    }
}
