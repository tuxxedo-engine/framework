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

namespace Integration\Console\Output;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\StreamOutput;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class StreamOutputEnvTest extends TestCase
{
    public function testAutoStripsWhenNoColorIsSetEvenOnATerminal(): void
    {
        \putenv('NO_COLOR=1');

        $stream = new BufferedOutputStream(
            isTerminal: true,
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

    public function testAutoDecoratesWhenForceColorIsSetEvenOnAPipe(): void
    {
        \putenv('FORCE_COLOR=1');

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
            "\033[31mx\033[0m",
            $stream->bytes,
        );
    }

    public function testAutoDecoratesOnATerminalWithoutNoColor(): void
    {
        \putenv('NO_COLOR');
        \putenv('FORCE_COLOR');

        $stream = new BufferedOutputStream(
            isTerminal: true,
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
            "\033[31mx\033[0m",
            $stream->bytes,
        );
    }

    public function testAutoStripsWhenNoColorOverridesForceColor(): void
    {
        \putenv('NO_COLOR=1');
        \putenv('FORCE_COLOR=1');

        $stream = new BufferedOutputStream(
            isTerminal: true,
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
}
