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

namespace Tuxxedo\Model\Hydrator\Coercer;

use Tuxxedo\Model\Attribute\Column\DateFormat;

class TimestampCoercer extends AbstractDateTimeFormatCoercer
{
    public function __construct(
        DateFormat|string $format = DateFormat::DEFAULT,
    ) {
        parent::__construct(
            $format instanceof DateFormat
                ? $format->value
                : $format,
        );
    }
}
