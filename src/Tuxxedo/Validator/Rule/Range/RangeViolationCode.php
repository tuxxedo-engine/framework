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

namespace Tuxxedo\Validator\Rule\Range;

use Tuxxedo\Validator\ViolationCodeInterface;

enum RangeViolationCode: string implements ViolationCodeInterface
{
    case BELOW_MIN = 'validator.range.below-min';
    case ABOVE_MAX = 'validator.range.above-max';
}
