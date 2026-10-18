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

namespace Tuxxedo\Validator\Rule\SupportedLocale;

use Tuxxedo\Validator\ViolationCodeInterface;

enum SupportedLocaleViolationCode: string implements ViolationCodeInterface
{
    case NOT_SUPPORTED = 'validator.supported-locale.not-supported';
}
