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

namespace Tuxxedo\Validator\Rule\SuffixedWith;

use Tuxxedo\Validator\ViolationCodeInterface;

enum SuffixedWithViolationCode: string implements ViolationCodeInterface
{
    case MISSING_SUFFIX = 'validator.suffixed-with.missing-suffix';
}
