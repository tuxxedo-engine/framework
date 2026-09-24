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

namespace Tuxxedo\Validator\Rule\Instant;

use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\TemporalException;

class InstantCoercion
{
    public static function coerce(
        mixed $value,
    ): ?InstantInterface {
        if ($value instanceof InstantInterface) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Instant::fromDateTime(
                dateTime: \DateTimeImmutable::createFromInterface($value),
            );
        }

        if (\is_string($value)) {
            try {
                return Instant::parse(input: $value);
            } catch (TemporalException) {
                return null;
            }
        }

        return null;
    }
}
