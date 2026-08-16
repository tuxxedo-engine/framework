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

namespace Tuxxedo\Validator\Rule\NegativeInteger;

use Tuxxedo\Validator\ViolationCodeInterface;

enum NegativeIntegerViolationCode: string implements ViolationCodeInterface
{
    case NOT_NEGATIVE = 'validator.negative-integer.not-negative';
}
