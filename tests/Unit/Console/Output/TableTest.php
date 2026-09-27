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
use Tuxxedo\Console\Output\StyledCell;
use Tuxxedo\Console\Output\Table;
use Tuxxedo\Console\Output\TableCharacterSet;

class TableTest extends TestCase
{
    public function testRendersHeadersAndRowsWithAsciiCharacters(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [
                'a',
                'b',
            ],
            rows: [
                [
                    '1',
                    '2',
                ],
            ],
            characters: TableCharacterSet::ascii(),
        );

        $table->render($output);

        $expected = "+---+---+" . \PHP_EOL
            . "| a | b |" . \PHP_EOL
            . "+---+---+" . \PHP_EOL
            . "| 1 | 2 |" . \PHP_EOL
            . "+---+---+" . \PHP_EOL;

        self::assertSame($expected, $stream->bytes);
    }

    public function testColumnWidthsGrowToLongestValueInEitherHeaderOrRow(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [
                'x',
            ],
            rows: [
                [
                    'longer',
                ],
            ],
            characters: TableCharacterSet::ascii(),
        );

        $table->render($output);

        self::assertStringContainsString(
            '| x      |',
            $stream->bytes,
        );

        self::assertStringContainsString(
            '| longer |',
            $stream->bytes,
        );
    }

    public function testEmptyRowsRenderHeaderOnly(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [
                'h',
            ],
            rows: [],
            characters: TableCharacterSet::ascii(),
        );

        $table->render($output);

        $expected = '+---+' . \PHP_EOL
            . '| h |' . \PHP_EOL
            . '+---+' . \PHP_EOL
            . '+---+' . \PHP_EOL;

        self::assertSame($expected, $stream->bytes);
    }

    public function testEmptyHeadersSkipHeaderRowAndMiddleBorder(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [],
            rows: [
                [
                    'only',
                ],
            ],
            characters: TableCharacterSet::ascii(),
        );

        $table->render($output);

        $expected = '+------+' . \PHP_EOL
            . '| only |' . \PHP_EOL
            . '+------+' . \PHP_EOL;

        self::assertSame($expected, $stream->bytes);
    }

    public function testHeaderStyleWrapsHeaderCells(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $table = new Table(
            headers: [
                'H',
            ],
            rows: [
                [
                    '1',
                ],
            ],
            characters: TableCharacterSet::ascii(),
            headerStyle: new Style(
                foreground: Color::RED,
            ),
        );

        $table->render($output);

        self::assertStringContainsString(
            "\033[31m H \033[0m",
            $stream->bytes,
        );

        self::assertStringNotContainsString(
            "\033[31m 1 \033[0m",
            $stream->bytes,
        );
    }

    public function testBorderStyleWrapsBorderCharacters(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $table = new Table(
            headers: [
                'h',
            ],
            rows: [
                [
                    '1',
                ],
            ],
            characters: TableCharacterSet::ascii(),
            borderStyle: new Style(
                foreground: Color::BLUE,
            ),
        );

        $table->render($output);

        self::assertStringContainsString(
            "\033[34m+---+\033[0m",
            $stream->bytes,
        );

        self::assertStringContainsString(
            "\033[34m|\033[0m",
            $stream->bytes,
        );
    }

    public function testStyledCellOverridesHeaderStyleForThatCell(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $table = new Table(
            headers: [
                new StyledCell(
                    value: 'H',
                    style: new Style(
                        foreground: Color::GREEN,
                    ),
                ),
            ],
            rows: [],
            characters: TableCharacterSet::ascii(),
            headerStyle: new Style(
                foreground: Color::RED,
            ),
        );

        $table->render($output);

        self::assertStringContainsString(
            "\033[32m H \033[0m",
            $stream->bytes,
        );

        self::assertStringNotContainsString(
            "\033[31m H \033[0m",
            $stream->bytes,
        );
    }

    public function testStyledCellInRowGetsItsOwnStyle(): void
    {
        $stream = new BufferedOutputStream();
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::ALWAYS,
        );

        $table = new Table(
            headers: [],
            rows: [
                [
                    new StyledCell(
                        value: 'x',
                        style: new Style(
                            foreground: Color::YELLOW,
                        ),
                    ),
                ],
            ],
            characters: TableCharacterSet::ascii(),
        );

        $table->render($output);

        self::assertStringContainsString(
            "\033[33m x \033[0m",
            $stream->bytes,
        );
    }

    public function testDefaultsToAsciiOnNonInteractiveOutput(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: false,
        );
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [
                'h',
            ],
            rows: [],
        );

        $table->render($output);

        self::assertStringContainsString(
            '+---+',
            $stream->bytes,
        );

        self::assertStringNotContainsString(
            '┌',
            $stream->bytes,
        );
    }

    public function testDefaultsToUnicodeOnInteractiveOutput(): void
    {
        $stream = new BufferedOutputStream(
            isTerminal: true,
        );
        $output = new StreamOutput(
            stream: $stream,
            decorationMode: DecorationMode::NEVER,
        );

        $table = new Table(
            headers: [
                'h',
            ],
            rows: [],
        );

        $table->render($output);

        self::assertStringContainsString(
            '┌',
            $stream->bytes,
        );

        self::assertStringNotContainsString(
            '+---+',
            $stream->bytes,
        );
    }
}
