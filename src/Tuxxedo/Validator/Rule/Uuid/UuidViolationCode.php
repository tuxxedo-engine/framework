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

namespace Tuxxedo\Validator\Rule\Uuid;

use Tuxxedo\Validator\ViolationCodeInterface;

enum UuidViolationCode: string implements ViolationCodeInterface
{
    case INVALID_FORMAT = 'validator.uuid.invalid-format';
}
