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

namespace Tuxxedo\Validator;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Validator::class, lifecycle: Lifecycle::SINGLETON)]
interface ValidatorInterface
{
    #[\NoDiscard]
    public function validate(
        object $target,
        ?string $group = null,
    ): ValidationResult;

    /**
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function validateOrThrow(
        object $target,
        ?string $group = null,
    ): void;
}
