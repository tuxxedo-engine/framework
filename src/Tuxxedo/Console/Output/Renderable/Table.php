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

namespace Tuxxedo\Console\Output\Renderable;

use Tuxxedo\Console\Output\OutputInterface;

class Table implements RenderableInterface
{
    private const CHARS_UNICODE = [
        'top_left' => '┌',
        'top_mid' => '┬',
        'top_right' => '┐',
        'mid_left' => '├',
        'mid_mid' => '┼',
        'mid_right' => '┤',
        'bot_left' => '└',
        'bot_mid' => '┴',
        'bot_right' => '┘',
        'vert' => '│',
        'horiz' => '─',
    ];

    private const CHARS_ASCII = [
        'top_left' => '+',
        'top_mid' => '+',
        'top_right' => '+',
        'mid_left' => '+',
        'mid_mid' => '+',
        'mid_right' => '+',
        'bot_left' => '+',
        'bot_mid' => '+',
        'bot_right' => '+',
        'vert' => '|',
        'horiz' => '-',
    ];

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
    ) {
    }

    public function renderTo(
        OutputInterface $output,
    ): void {
        $widths = $this->computeColumnWidths();
        $chars = $output->isInteractive
            ? self::CHARS_UNICODE
            : self::CHARS_ASCII;

        $output->line(
            $this->buildBorder(
                widths: $widths,
                left: $chars['top_left'],
                mid: $chars['top_mid'],
                right: $chars['top_right'],
                horiz: $chars['horiz'],
            ),
        );

        if ($this->headers !== []) {
            $output->line(
                $this->buildRow(
                    cells: $this->headers,
                    widths: $widths,
                    vert: $chars['vert'],
                ),
            );

            $output->line(
                $this->buildBorder(
                    widths: $widths,
                    left: $chars['mid_left'],
                    mid: $chars['mid_mid'],
                    right: $chars['mid_right'],
                    horiz: $chars['horiz'],
                ),
            );
        }

        foreach ($this->rows as $row) {
            $output->line(
                $this->buildRow(
                    cells: $row,
                    widths: $widths,
                    vert: $chars['vert'],
                ),
            );
        }

        $output->line(
            $this->buildBorder(
                widths: $widths,
                left: $chars['bot_left'],
                mid: $chars['bot_mid'],
                right: $chars['bot_right'],
                horiz: $chars['horiz'],
            ),
        );
    }

    /**
     * @return list<int>
     */
    private function computeColumnWidths(): array
    {
        $widths = [];

        foreach ($this->headers as $index => $header) {
            $widths[$index] = \strlen($header);
        }

        foreach ($this->rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = \max($widths[$index] ?? 0, \strlen($cell));
            }
        }

        return \array_values($widths);
    }

    /**
     * @param list<int> $widths
     */
    private function buildBorder(
        array $widths,
        string $left,
        string $mid,
        string $right,
        string $horiz,
    ): string {
        $parts = [];

        foreach ($widths as $width) {
            $parts[] = \str_repeat($horiz, $width + 2);
        }

        return $left . \join($mid, $parts) . $right;
    }

    /**
     * @param list<string> $cells
     * @param list<int> $widths
     */
    private function buildRow(
        array $cells,
        array $widths,
        string $vert,
    ): string {
        $parts = [];

        foreach ($widths as $index => $width) {
            $cell = $cells[$index] ?? '';
            $parts[] = ' ' . \str_pad($cell, $width) . ' ';
        }

        return $vert . \join($vert, $parts) . $vert;
    }
}
