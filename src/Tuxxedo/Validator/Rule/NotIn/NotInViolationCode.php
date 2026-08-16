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

namespace Tuxxedo\Validator\Rule\NotIn;

use Tuxxedo\Validator\ViolationCodeInterface;

enum NotInViolationCode: string implements ViolationCodeInterface
{
    case IN_LIST = 'validator.not-in.in-list';
}
