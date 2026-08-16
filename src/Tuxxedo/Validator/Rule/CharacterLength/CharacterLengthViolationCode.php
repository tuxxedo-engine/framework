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

namespace Tuxxedo\Validator\Rule\CharacterLength;

use Tuxxedo\Validator\ViolationCodeInterface;

enum CharacterLengthViolationCode: string implements ViolationCodeInterface
{
    case TOO_SHORT = 'validator.character-length.too-short';
    case TOO_LONG = 'validator.character-length.too-long';
}
