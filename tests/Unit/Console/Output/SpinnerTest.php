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
use Tuxxedo\Console\Output\FrameSequence;
use Tuxxedo\Console\Output\Spinner;
use Tuxxedo\Console\Output\SpinnerTheme;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Console\Output\Style\Style;
use Tuxxedo\Temporal\Duration;

class SpinnerTest extends TestCase
{
    public function testNonInteractiveConstructorEmitsMessageLineWhenMessageProvided(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            message: 'working',
        );

        self::assertSame(
            'working' . \PHP_EOL,
            $stream->bytes,
        );
    }

    public function testNonInteractiveConstructorEmitsNothingWithoutMessage(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        self::assertSame(
            '',
            $stream->bytes,
        );
    }

    public function testNonInteractiveTickDoesNothing(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        $before = $stream->bytes;

        $spinner->tick();

        self::assertSame($before, $stream->bytes);
    }

    public function testInteractiveConstructorDrawsFirstFrame(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );
        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    'A',
                    'B',
                ],
                interval: Duration::fromMilliseconds(10),
            ),
        );

        new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            theme: $theme,
        );

        self::assertStringContainsString(
            "\r\033[2K",
            $stream->bytes,
        );
        self::assertStringContainsString('A', $stream->bytes);
    }

    public function testInteractiveTickAdvancesFrame(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    'A',
                    'B',
                ],
                interval: Duration::fromMilliseconds(0),
            ),
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            theme: $theme,
        );

        $spinner->tick();

        self::assertStringContainsString('B', $stream->bytes);
    }

    public function testFinishEmitsClearLineWhenInteractive(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        $lengthBefore = \strlen($stream->bytes);

        $spinner->finish();

        self::assertStringEndsWith(
            "\r\033[2K",
            \substr($stream->bytes, $lengthBefore),
        );
    }

    public function testFinishIsNoopWhenNonInteractive(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            message: 'x',
        );

        $before = $stream->bytes;

        $spinner->finish();

        self::assertSame($before, $stream->bytes);
    }

    public function testMessageStyleWrapsMessageWhenNonInteractive(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    '.',
                ],
                interval: Duration::fromMilliseconds(10),
            ),
            messageStyle: new Style(
                foreground: Color::MAGENTA,
            ),
        );

        new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::ALWAYS,
            ),
            theme: $theme,
            message: 'busy',
        );

        self::assertStringContainsString(
            "\033[35mbusy\033[0m",
            $stream->bytes,
        );
    }

    public function testFrameStyleWrapsFrameWhenInteractive(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    'X',
                ],
                interval: Duration::fromMilliseconds(10),
            ),
            frameStyle: new Style(
                foreground: Color::CYAN,
            ),
        );

        new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::ALWAYS,
            ),
            theme: $theme,
        );

        self::assertStringContainsString(
            "\033[36mX\033[0m",
            $stream->bytes,
        );
    }

    public function testInteractiveTickThrottlesRepeatCalls(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );
        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    'A',
                    'B',
                ],
                interval: Duration::fromMilliseconds(60_000),
            ),
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            theme: $theme,
        );

        $spinner->tick();
        $bytesAfterFirstTick = $stream->bytes;
        $spinner->tick();

        self::assertSame(
            $bytesAfterFirstTick,
            $stream->bytes,
        );
    }

    public function testSetMessageRedrawsWithNewMessage(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );
        $theme = new SpinnerTheme(
            frames: FrameSequence::of(
                frames: [
                    '.',
                ],
                interval: Duration::fromMilliseconds(10),
            ),
        );

        $spinner = new Spinner(
            output: new StreamOutput(
                stream: $stream,
                decorationMode: DecorationMode::NEVER,
            ),
            theme: $theme,
        );

        $spinner->setMessage('halfway');

        self::assertStringContainsString(
            'halfway',
            $stream->bytes,
        );
    }
}
