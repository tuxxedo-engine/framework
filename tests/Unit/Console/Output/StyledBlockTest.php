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

namespace Unit\Console\Output;

use PHPUnit\Framework\TestCase;
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Console\Output\Style\Style;
use Tuxxedo\Console\Output\StyledBlock;

class StyledBlockTest extends TestCase
{
    public function testEmptyLinesEmitNothing(): void
    {
        $stream = new BufferedOutputStream();

        (new StyledBlock(
            lines: [],
            style: Style::info(),
        ))->render(
            output: $this->makeOutput(stream: $stream),
        );

        self::assertSame('', $stream->bytes);
    }

    public function testSingleLineIsPaddedAndStyled(): void
    {
        $stream = new BufferedOutputStream();

        (new StyledBlock(
            lines: [
                'hi',
            ],
            style: new Style(
                foreground: Color::CYAN,
            ),
        ))->render(
            output: $this->makeOutput(
                stream: $stream,
                decorationMode: DecorationMode::ALWAYS,
            ),
        );

        self::assertSame(
            "\033[36m hi \033[0m" . \PHP_EOL,
            $stream->bytes,
        );
    }

    public function testLinesAreAlignedToLongestWidth(): void
    {
        $stream = new BufferedOutputStream();

        (new StyledBlock(
            lines: [
                'a',
                'longer',
                'b',
            ],
            style: Style::info(),
        ))->render(
            output: $this->makeOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        $expected = ' a      ' . \PHP_EOL
            . ' longer ' . \PHP_EOL
            . ' b      ' . \PHP_EOL;

        self::assertSame($expected, $stream->bytes);
    }

    public function testCustomPaddingIsApplied(): void
    {
        $stream = new BufferedOutputStream();

        (new StyledBlock(
            lines: [
                'x',
            ],
            style: Style::info(),
            padding: 3,
        ))->render(
            output: $this->makeOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        self::assertSame(
            '   x   ' . \PHP_EOL,
            $stream->bytes,
        );
    }

    public function testZeroPaddingProducesNoSurroundingSpaces(): void
    {
        $stream = new BufferedOutputStream();

        (new StyledBlock(
            lines: [
                'x',
            ],
            style: Style::info(),
            padding: 0,
        ))->render(
            output: $this->makeOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        self::assertSame(
            'x' . \PHP_EOL,
            $stream->bytes,
        );
    }

    private function makeOutput(
        BufferedOutputStream $stream,
        DecorationMode $decorationMode = DecorationMode::NEVER,
    ): StreamOutput {
        return new StreamOutput(
            stream: $stream,
            decorationMode: $decorationMode,
        );
    }
}
