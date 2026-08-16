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

namespace Tuxxedo\Validator\Rule\Base64;

use Tuxxedo\Validator\ViolationCodeInterface;

enum Base64ViolationCode: string implements ViolationCodeInterface
{
    case INVALID_FORMAT = 'validator.base64.invalid-format';
}
