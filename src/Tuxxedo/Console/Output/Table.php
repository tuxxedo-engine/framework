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

use Tuxxedo\Console\Output\Style\Style;

class Table implements TableInterface
{
    /**
     * @param list<string|StyledCell> $headers
     * @param list<list<string|StyledCell>> $rows
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
        public readonly ?TableCharacterSet $characters = null,
        public readonly ?Style $headerStyle = null,
        public readonly ?Style $borderStyle = null,
    ) {
    }

    public function render(
        OutputInterface $output,
    ): void {
        $widths = $this->computeColumnWidths();
        $chars = $this->characters ?? ($output->isInteractive ? TableCharacterSet::unicode() : TableCharacterSet::ascii());

        $this->writeBorder(
            output: $output,
            widths: $widths,
            left: $chars->topLeft,
            junction: $chars->topJunction,
            right: $chars->topRight,
            horizontal: $chars->horizontal,
        );

        if ($this->headers !== []) {
            $this->writeRow(
                output: $output,
                cells: $this->headers,
                widths: $widths,
                vertical: $chars->vertical,
                rowStyle: $this->headerStyle,
            );

            $this->writeBorder(
                output: $output,
                widths: $widths,
                left: $chars->middleLeft,
                junction: $chars->middleJunction,
                right: $chars->middleRight,
                horizontal: $chars->horizontal,
            );
        }

        foreach ($this->rows as $row) {
            $this->writeRow(
                output: $output,
                cells: $row,
                widths: $widths,
                vertical: $chars->vertical,
                rowStyle: null,
            );
        }

        $this->writeBorder(
            output: $output,
            widths: $widths,
            left: $chars->bottomLeft,
            junction: $chars->bottomJunction,
            right: $chars->bottomRight,
            horizontal: $chars->horizontal,
        );
    }

    /**
     * @return list<int>
     */
    private function computeColumnWidths(): array
    {
        $widths = [];

        foreach ($this->headers as $index => $header) {
            $widths[$index] = \strlen(self::cellValue($header));
        }

        foreach ($this->rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = \max($widths[$index] ?? 0, \strlen(self::cellValue($cell)));
            }
        }

        return \array_values($widths);
    }

    /**
     * @param list<int> $widths
     */
    private function writeBorder(
        OutputInterface $output,
        array $widths,
        string $left,
        string $junction,
        string $right,
        string $horizontal,
    ): void {
        $parts = [];

        foreach ($widths as $width) {
            $parts[] = \str_repeat($horizontal, $width + 2);
        }

        $this->writeStyled(
            output: $output,
            bytes: $left . \join($junction, $parts) . $right,
            style: $this->borderStyle,
        );

        $output->line();
    }

    /**
     * @param list<string|StyledCell> $cells
     * @param list<int> $widths
     */
    private function writeRow(
        OutputInterface $output,
        array $cells,
        array $widths,
        string $vertical,
        ?Style $rowStyle,
    ): void {
        foreach ($widths as $index => $width) {
            $this->writeStyled(
                output: $output,
                bytes: $vertical,
                style: $this->borderStyle,
            );

            $cell = $cells[$index] ?? '';
            $value = self::cellValue($cell);
            $cellStyle = $cell instanceof StyledCell
                ? $cell->style
                : $rowStyle;

            $this->writeStyled(
                output: $output,
                bytes: ' ' . \str_pad($value, $width) . ' ',
                style: $cellStyle,
            );
        }

        $this->writeStyled(
            output: $output,
            bytes: $vertical,
            style: $this->borderStyle,
        );

        $output->line();
    }

    private function writeStyled(
        OutputInterface $output,
        string $bytes,
        ?Style $style,
    ): void {
        if ($style === null) {
            $output->write($bytes);

            return;
        }

        $output->styled($bytes, $style);
    }

    private static function cellValue(
        string|StyledCell $cell,
    ): string {
        return $cell instanceof StyledCell
            ? $cell->value
            : $cell;
    }
}
