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

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;

class FrameSequence
{
    /**
     * @param list<string> $frames
     */
    private function __construct(
        public readonly array $frames,
        public readonly DurationInterface $interval,
    ) {
    }

    /**
     * @param list<string> $frames
     *
     * @throws ConsoleException
     */
    public static function of(
        array $frames,
        DurationInterface $interval,
    ): self {
        if ($frames === []) {
            throw ConsoleException::fromEmptyFrameSequence();
        }

        return new self(
            frames: $frames,
            interval: $interval,
        );
    }

    public static function brailleDots(): self
    {
        return new self(
            frames: [
                '⣾',
                '⣽',
                '⣻',
                '⢿',
                '⡿',
                '⣟',
                '⣯',
                '⣷',
            ],
            interval: Duration::fromMilliseconds(80),
        );
    }
}
