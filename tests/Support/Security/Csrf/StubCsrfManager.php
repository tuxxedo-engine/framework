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

namespace Support\Security\Csrf;

use Tuxxedo\Security\Csrf\CsrfManagerInterface;

class StubCsrfManager implements CsrfManagerInterface
{
    public string $fieldName = 'csrf_token';
    public ?string $lastValidatedToken = null;

    public function __construct(
        public bool $validResult = true,
    ) {
    }

    public function getToken(): string
    {
        return 'stub-token';
    }

    public function regenerate(): string
    {
        return 'stub-token';
    }

    public function validate(
        string $token,
    ): bool {
        $this->lastValidatedToken = $token;

        return $this->validResult;
    }

    public function clear(): void
    {
    }
}
