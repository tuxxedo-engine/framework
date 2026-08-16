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

namespace Tuxxedo\Validator\Rule\Hostname;

use Tuxxedo\Validator\ViolationCodeInterface;

enum HostnameViolationCode: string implements ViolationCodeInterface
{
    case INVALID_FORMAT = 'validator.hostname.invalid-format';
}
