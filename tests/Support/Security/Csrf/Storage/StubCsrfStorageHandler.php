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

namespace Support\Security\Csrf\Storage;

use Tuxxedo\Security\Csrf\Storage\CsrfStorageHandlerInterface;

class StubCsrfStorageHandler implements CsrfStorageHandlerInterface
{
    public function __construct(
        public ?string $token = null,
    ) {
    }

    public function clear(): void
    {
        $this->token = null;
    }

    public function get(): ?string
    {
        return $this->token;
    }

    public function set(
        string $token,
    ): void {
        $this->token = $token;
    }
}
