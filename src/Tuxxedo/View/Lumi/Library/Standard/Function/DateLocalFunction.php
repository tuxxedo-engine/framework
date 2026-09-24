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

use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateLocalFunction implements FunctionInterface
{
    public private(set) string $name = 'date_local';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        $value = $arguments[0];

        $tzArgument = $arguments[1] ?? null;

        /** @var string $format */
        $format = $arguments[2] ?? 'Y-m-d H:i:s';

        return TemporalCoercion::toInstant($value)
            ->withTimeZone(self::resolveTimeZone($tzArgument))
            ->format($format);
    }

    private static function resolveTimeZone(
        mixed $argument,
    ): TimeZoneInterface {
        if ($argument instanceof TimeZoneInterface) {
            return $argument;
        }

        if (\is_string($argument)) {
            return TimeZone::parse($argument);
        }

        return TimeZone::parse(\date_default_timezone_get());
    }
}
