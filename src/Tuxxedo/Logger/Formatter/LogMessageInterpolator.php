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

namespace Tuxxedo\Logger\Formatter;

use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Text\Interpolator\Interpolator;
use Tuxxedo\Text\Interpolator\InterpolatorInterface;

class LogMessageInterpolator implements LogMessageInterpolatorInterface
{
    private readonly InterpolatorInterface $interpolator;

    public function __construct(
        ?InterpolatorInterface $interpolator = null,
    ) {
        $this->interpolator = $interpolator ?? new Interpolator();
    }

    public function interpolate(
        LogRecordInterface $record,
    ): string {
        $replacements = [];

        foreach ($record->context as $key => $value) {
            $rendered = self::render(
                value: $value,
            );

            if ($rendered !== null) {
                $replacements[$key] = $rendered;
            }
        }

        return $this->interpolator->interpolate(
            template: $record->message,
            replacements: $replacements,
        );
    }

    private static function render(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value
                ? 'true'
                : 'false';
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_float($value) && \is_nan($value)) {
            return 'NAN';
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
