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

class DateCoercer extends AbstractDateTimeFormatCoercer
{
    public function __construct(
        private readonly DateFormat $format = DateFormat::DEFAULT,
    ) {
        parent::__construct($format->value);
    }
}
