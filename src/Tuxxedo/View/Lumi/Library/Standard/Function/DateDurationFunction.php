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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\HumanizedStyle;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateDurationFunction implements FunctionInterface
{
    public private(set) string $name = 'date_duration';
    public private(set) array $aliases = [];

    private readonly Humanizer $humanizer;

    public function __construct(
        ?Humanizer $humanizer = null,
        ?ClockInterface $clock = null,
    ) {
        $this->humanizer = $humanizer ?? new Humanizer($clock ?? new SystemClock());
    }

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        $value = $arguments[0];
        $styleArgument = $arguments[1] ?? 'long';

        /** @var string $styleArgument */

        return $this->humanizer->formatDuration(
            duration: self::coerceDuration($value),
            style: self::resolveStyle($styleArgument),
        );
    }

    private static function coerceDuration(
        mixed $value,
    ): DurationInterface {
        if ($value instanceof DurationInterface) {
            return $value;
        }

        if (\is_int($value)) {
            return Duration::fromSeconds($value);
        }

        /** @var string $value */

        return Duration::fromIso8601DurationString($value);
    }

    private static function resolveStyle(
        string $argument,
    ): HumanizedStyle {
        return match (\strtolower($argument)) {
            'short' => HumanizedStyle::SHORT,
            'narrow' => HumanizedStyle::NARROW,
            default => HumanizedStyle::LONG,
        };
    }
}
