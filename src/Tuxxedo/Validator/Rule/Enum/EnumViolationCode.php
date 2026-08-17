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

namespace Tuxxedo\Validator\Rule\Enum;

use Tuxxedo\Validator\ViolationCodeInterface;

enum EnumViolationCode: string implements ViolationCodeInterface
{
    case WRONG_INSTANCE = 'validator.enum.wrong-instance';
}
