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

namespace Unit\Console\Output\Style;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\Style\Style;

class StyleTest extends TestCase
{
    public function testDimFactoryProducesDimDecoration(): void
    {
        self::assertSame(
            "\033[2mtext\033[0m",
            Style::dim()->apply(bytes: 'text'),
        );
    }

    public function testPrimaryFactoryProducesLightBlueForeground(): void
    {
        self::assertSame(
            "\033[94mtext\033[0m",
            Style::primary()->apply(bytes: 'text'),
        );
    }

    public function testApplyReturnsBytesUnchangedWhenStyleHasNoCodes(): void
    {
        self::assertSame(
            'text',
            (new Style())->apply(bytes: 'text'),
        );
    }

    public function testBackgroundOnlyStyleEmitsBackgroundCode(): void
    {
        self::assertSame(
            "\033[47mtext\033[0m",
            (new Style(
                background: Color::WHITE,
            ))->apply(bytes: 'text'),
        );
    }

    public function testForegroundAndBackgroundCombinedInSameSgr(): void
    {
        self::assertSame(
            "\033[31;47mtext\033[0m",
            (new Style(
                foreground: Color::RED,
                background: Color::WHITE,
            ))->apply(bytes: 'text'),
        );
    }
}
