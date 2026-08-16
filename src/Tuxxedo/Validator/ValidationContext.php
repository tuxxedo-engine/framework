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

class ValidationContext implements ValidationContextInterface
{
    public function __construct(
        public readonly string $currentPath,
        public readonly ?object $rootObject = null,
        public readonly ?string $group = null,
    ) {
    }
}
