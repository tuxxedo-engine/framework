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
use Tuxxedo\Console\Output\ProgressBar;
use Tuxxedo\Console\Output\ProgressBarTheme;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Console\Output\Style\Style;

class ProgressBarTest extends TestCase
{
    public function testConstructorDrawsInitialZeroPercentBar(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 4,
        );

        self::assertStringContainsString(
            '[',
            $stream->bytes,
        );

        self::assertStringContainsString(
            '0%',
            $stream->bytes,
        );
    }

    public function testNonInteractiveEmitsAtEachMilestone(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 4,
        );

        $bar->advance();
        $bar->advance();
        $bar->advance();
        $bar->advance();

        self::assertStringContainsString(' 25%', $stream->bytes);
        self::assertStringContainsString(' 50%', $stream->bytes);
        self::assertStringContainsString(' 75%', $stream->bytes);
        self::assertStringContainsString('100%', $stream->bytes);
    }

    public function testFinishAppendsFinalNewline(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 2,
        );

        $bar->finish();

        self::assertStringEndsWith(
            \PHP_EOL,
            $stream->bytes,
        );

        self::assertStringContainsString('100%', $stream->bytes);
    }

    public function testInteractiveDrawsWithClearLineSequence(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 1,
        );

        self::assertStringContainsString(
            "\r\033[2K",
            $stream->bytes,
        );
    }

    public function testFilledStyleWrapsFilledSegment(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::ALWAYS,
            ),
            total: 1,
            theme: new ProgressBarTheme(
                width: 4,
                filledSegment: '=',
                emptySegment: ' ',
                head: '',
                leadingCap: '[',
                trailingCap: ']',
                filledStyle: new Style(
                    foreground: Color::GREEN,
                ),
            ),
        );

        $bar->finish();

        self::assertStringContainsString(
            "\033[32m====\033[0m",
            $stream->bytes,
        );
    }

    public function testMessageIsAppendedAfterBar(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 1,
        );

        $bar->setMessage('working');
        $bar->finish();

        self::assertStringContainsString(
            'working',
            $stream->bytes,
        );
    }

    public function testIterateAdvancesPerItemAndFinishes(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 4,
        );

        $collected = [];

        foreach ($bar->iterate(['a', 'b', 'c', 'd']) as $item) {
            $collected[] = $item;
        }

        self::assertSame(
            [
                'a',
                'b',
                'c',
                'd',
            ],
            $collected,
        );
        self::assertStringContainsString('100%', $stream->bytes);
        self::assertStringEndsWith(\PHP_EOL, $stream->bytes);
    }

    public function testAdvanceClampsAtTotal(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 2,
        );

        $bar->advance(10);
        $bar->finish();

        self::assertStringContainsString('100%', $stream->bytes);
    }

    public function testInteractiveAdvanceDrawsWhenReachingTotalDespiteThrottle(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 2,
        );

        $bar->advance();
        $bar->advance();

        self::assertStringContainsString(
            '100%',
            $stream->bytes,
        );
    }

    public function testInteractiveAdvanceSkipsRedrawWhenThrottled(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 100,
        );

        $bar->advance();
        $bytesAfterFirst = $stream->bytes;
        $bar->advance();

        self::assertSame(
            $bytesAfterFirst,
            $stream->bytes,
        );
    }

    public function testInteractiveSetMessageRedraws(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $bar = new ProgressBar(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            total: 1,
        );

        $bytesBefore = $stream->bytes;
        $bar->setMessage('halfway');

        self::assertNotSame(
            $bytesBefore,
            $stream->bytes,
        );
        self::assertStringContainsString(
            'halfway',
            $stream->bytes,
        );
    }
}
