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

namespace Tuxxedo\Validator\Rule\LanguageCode;

use Tuxxedo\Validator\ViolationCodeInterface;

enum LanguageCodeViolationCode: string implements ViolationCodeInterface
{
    case NOT_RECOGNIZED = 'validator.language-code.not-recognized';
}
