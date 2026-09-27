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
use Tuxxedo\Console\Output\Style\Decoration;
use Tuxxedo\Console\Output\Style\Style;

class StreamOutputTest extends TestCase
{
    public function testWriteEmitsRawBytesWhenNoColorsRequested(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->write('hello');

        self::assertSame(
            'hello',
            $stream->bytes,
        );
    }

    public function testWriteEmitsForegroundSgrWhenDecorationAllowed(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->write(
            bytes: 'hi',
            foreground: Color::RED,
        );

        self::assertSame(
            "\033[31mhi\033[0m",
            $stream->bytes,
        );
    }

    public function testWriteJoinsForegroundAndBackgroundCodes(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->write(
            bytes: 'x',
            foreground: Color::RED,
            background: Color::WHITE,
        );

        self::assertSame(
            "\033[31;47mx\033[0m",
            $stream->bytes,
        );
    }

    public function testWriteStripsColorsWhenDecorationModeNever(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $output->write(
            bytes: 'plain',
            foreground: Color::RED,
        );

        self::assertSame(
            'plain',
            $stream->bytes,
        );
    }

    public function testLineAppendsEol(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $output->line('row');

        self::assertSame(
            'row' . \PHP_EOL,
            $stream->bytes,
        );
    }

    public function testLineWithColorEmitsSgrWhenDecorationAllowed(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->line(
            text: 'ok',
            foreground: Color::GREEN,
        );

        self::assertSame(
            "\033[32mok\033[0m" . \PHP_EOL,
            $stream->bytes,
        );
    }

    public function testStyledAppliesStyleWhenDecorationAllowed(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $style = new Style(
            foreground: Color::CYAN,
            decorations: [
                Decoration::BOLD,
            ],
        );

        $output->styled(
            bytes: 'yo',
            style: $style,
        );

        self::assertSame(
            "\033[1;36myo\033[0m",
            $stream->bytes,
        );
    }

    public function testStyledStripsWhenDecorationModeNever(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );

        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $output->styled(
            bytes: 'yo',
            style: Style::error(),
        );

        self::assertSame(
            'yo',
            $stream->bytes,
        );
    }

    public function testErrorUsesLightRedBold(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->error('boom');

        self::assertSame(
            "\033[1;91mboom\033[0m",
            $stream->bytes,
        );
    }

    public function testSuccessUsesLightGreen(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->success('done');

        self::assertSame(
            "\033[92mdone\033[0m",
            $stream->bytes,
        );
    }

    public function testWarningUsesLightYellow(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->warning('watch');

        self::assertSame(
            "\033[93mwatch\033[0m",
            $stream->bytes,
        );
    }

    public function testInfoUsesLightCyan(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $output->info('note');

        self::assertSame(
            "\033[96mnote\033[0m",
            $stream->bytes,
        );
    }

    public function testAutoModeStripsWhenStreamIsNotATerminal(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );

        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::AUTO,
        );

        $output->write(
            bytes: 'x',
            foreground: Color::RED,
        );

        self::assertSame(
            'x',
            $stream->bytes,
        );
    }

    public function testIsInteractiveReflectsStreamTerminal(): void
    {
        $terminal = new StreamOutput(
            stream: new BufferedOutputStream(
                isTerminal: true,
            ),
        );

        $piped = new StreamOutput(
            stream: new BufferedOutputStream(),
        );

        self::assertTrue($terminal->isInteractive);
        self::assertFalse($piped->isInteractive);
    }
}
