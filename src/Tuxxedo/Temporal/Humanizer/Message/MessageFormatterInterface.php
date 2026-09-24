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

namespace Tuxxedo\Temporal\Humanizer\Message;

use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\RelativeDirection;
use Tuxxedo\Temporal\Humanizer\RelativeUnit;

interface MessageFormatterInterface
{
    public function forJustNow(): string;

    public function forRelativeMoment(
        int $count,
        RelativeUnit $unit,
        RelativeDirection $direction,
    ): string;

    /**
     * @param list<array{int, RelativeUnit}> $parts
     */
    public function forDuration(
        array $parts,
        HumanizedStyle $style,
    ): string;

    public function forZeroDuration(
        HumanizedStyle $style,
    ): string;
}
