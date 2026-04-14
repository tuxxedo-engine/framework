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

namespace Tuxxedo\Security\Csrf;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: CsrfManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface CsrfManagerInterface
{
    public string $fieldName {
        get;
    }

    public function getToken(): string;
    public function regenerate(): string;

    public function validate(
        string $token,
    ): bool;

    public function clear(): void;
}
