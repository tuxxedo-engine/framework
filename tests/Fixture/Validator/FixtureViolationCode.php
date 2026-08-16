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

namespace Fixture\Validator;

use Tuxxedo\Validator\ViolationCodeInterface;

enum FixtureViolationCode: string implements ViolationCodeInterface
{
    case ALWAYS_FAIL = 'fixture.always-fail';
    case CONTAINER_AWARE_FAIL = 'fixture.container-aware-fail';
    case ODD_NUMBER = 'fixture.odd-number';
}
