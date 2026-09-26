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

class StyledBlock
{
    /**
     * @param list<string> $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly Style $style,
        public readonly int $padding = 1,
    ) {
    }

    public function render(
        OutputInterface $output,
    ): void {
        if ($this->lines === []) {
            return;
        }

        $pad = \str_repeat(' ', $this->padding);
        $maxWidth = 0;

        foreach ($this->lines as $line) {
            $length = \strlen($line);

            if ($length > $maxWidth) {
                $maxWidth = $length;
            }
        }

        foreach ($this->lines as $line) {
            $output->styled(
                bytes: $pad . \str_pad($line, $maxWidth) . $pad,
                style: $this->style,
            );
            $output->line();
        }
    }
}
