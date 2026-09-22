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

class TableCharacterSet
{
    public function __construct(
        public readonly string $topLeft,
        public readonly string $topJunction,
        public readonly string $topRight,
        public readonly string $middleLeft,
        public readonly string $middleJunction,
        public readonly string $middleRight,
        public readonly string $bottomLeft,
        public readonly string $bottomJunction,
        public readonly string $bottomRight,
        public readonly string $vertical,
        public readonly string $horizontal,
    ) {
    }

    public static function unicode(): self
    {
        return new self(
            topLeft: '┌',
            topJunction: '┬',
            topRight: '┐',
            middleLeft: '├',
            middleJunction: '┼',
            middleRight: '┤',
            bottomLeft: '└',
            bottomJunction: '┴',
            bottomRight: '┘',
            vertical: '│',
            horizontal: '─',
        );
    }

    public static function ascii(): self
    {
        return new self(
            topLeft: '+',
            topJunction: '+',
            topRight: '+',
            middleLeft: '+',
            middleJunction: '+',
            middleRight: '+',
            bottomLeft: '+',
            bottomJunction: '+',
            bottomRight: '+',
            vertical: '|',
            horizontal: '-',
        );
    }
}
