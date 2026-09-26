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

namespace Tuxxedo\Console\Output\Style;

use Tuxxedo\Console\Output\Color;

class Style
{
    /**
     * @param list<Decoration> $decorations
     */
    public function __construct(
        public readonly ?Color $foreground = null,
        public readonly ?Color $background = null,
        public readonly array $decorations = [],
    ) {
    }

    public static function error(): self
    {
        return new self(
            foreground: Color::LIGHT_RED,
            decorations: [
                Decoration::BOLD,
            ],
        );
    }

    public static function success(): self
    {
        return new self(
            foreground: Color::LIGHT_GREEN,
        );
    }

    public static function warning(): self
    {
        return new self(
            foreground: Color::LIGHT_YELLOW,
        );
    }

    public static function info(): self
    {
        return new self(
            foreground: Color::LIGHT_CYAN,
        );
    }

    public static function dim(): self
    {
        return new self(
            decorations: [
                Decoration::DIM,
            ],
        );
    }

    public static function primary(): self
    {
        return new self(
            foreground: Color::LIGHT_BLUE,
        );
    }

    public function apply(
        string $bytes,
    ): string {
        $codes = [];

        foreach ($this->decorations as $decoration) {
            $codes[] = $decoration->value;
        }

        if ($this->foreground !== null) {
            $codes[] = $this->foreground->foreground();
        }

        if ($this->background !== null) {
            $codes[] = $this->background->background();
        }

        if ($codes === []) {
            return $bytes;
        }

        return "\033[" . \join(';', \array_map(\strval(...), $codes)) . 'm' . $bytes . "\033[0m";
    }
}
