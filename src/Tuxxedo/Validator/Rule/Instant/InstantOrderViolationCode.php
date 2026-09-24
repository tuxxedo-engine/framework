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

use Tuxxedo\Validator\ViolationCodeInterface;

enum InstantOrderViolationCode: string implements ViolationCodeInterface
{
    case NOT_BEFORE = 'validator.instant.not-before';

    case NOT_AFTER = 'validator.instant.not-after';
}
