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

namespace Tuxxedo\Validator\Rule\Max;

use Tuxxedo\Validator\ViolationCodeInterface;

enum MaxViolationCode: string implements ViolationCodeInterface
{
    case ABOVE_MAX = 'validator.max.above-max';
}
