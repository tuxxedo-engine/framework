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

namespace Tuxxedo\Serializer;

use Tuxxedo\Validator\ViolationCodeInterface;

enum SerializerViolationCode: string implements ViolationCodeInterface
{
    case MISSING_KEY = 'serializer.missing-key';
    case UNKNOWN_KEY = 'serializer.unknown-key';
    case INVALID_VALUE = 'serializer.invalid-value';
}
