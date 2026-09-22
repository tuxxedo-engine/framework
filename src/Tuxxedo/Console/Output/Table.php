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

namespace Tuxxedo\Console\Output;

class Table implements TableInterface
{
    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
        public readonly ?TableCharacterSet $characters = null,
    ) {
    }

    public function render(
        OutputInterface $output,
    ): void {
        $widths = $this->computeColumnWidths();
        $chars = $this->characters ?? (
            $output->isInteractive
                ? TableCharacterSet::unicode()
                : TableCharacterSet::ascii()
        );

        $output->line(
            $this->buildBorder(
                widths: $widths,
                left: $chars->topLeft,
                junction: $chars->topJunction,
                right: $chars->topRight,
                horizontal: $chars->horizontal,
            ),
        );

        if ($this->headers !== []) {
            $output->line(
                $this->buildRow(
                    cells: $this->headers,
                    widths: $widths,
                    vertical: $chars->vertical,
                ),
            );

            $output->line(
                $this->buildBorder(
                    widths: $widths,
                    left: $chars->middleLeft,
                    junction: $chars->middleJunction,
                    right: $chars->middleRight,
                    horizontal: $chars->horizontal,
                ),
            );
        }

        foreach ($this->rows as $row) {
            $output->line(
                $this->buildRow(
                    cells: $row,
                    widths: $widths,
                    vertical: $chars->vertical,
                ),
            );
        }

        $output->line(
            $this->buildBorder(
                widths: $widths,
                left: $chars->bottomLeft,
                junction: $chars->bottomJunction,
                right: $chars->bottomRight,
                horizontal: $chars->horizontal,
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
        string $junction,
        string $right,
        string $horizontal,
    ): string {
        $parts = [];

        foreach ($widths as $width) {
            $parts[] = \str_repeat($horizontal, $width + 2);
        }

        return $left . \join($junction, $parts) . $right;
    }

    /**
     * @param list<string> $cells
     * @param list<int> $widths
     */
    private function buildRow(
        array $cells,
        array $widths,
        string $vertical,
    ): string {
        $parts = [];

        foreach ($widths as $index => $width) {
            $cell = $cells[$index] ?? '';
            $parts[] = ' ' . \str_pad($cell, $width) . ' ';
        }

        return $vertical . \join($vertical, $parts) . $vertical;
    }
}
