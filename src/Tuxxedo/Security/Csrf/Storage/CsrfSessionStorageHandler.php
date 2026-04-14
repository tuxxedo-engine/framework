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

namespace Tuxxedo\Security\Csrf\Storage;

use Tuxxedo\Session\SessionInterface;

class CsrfSessionStorageHandler implements CsrfStorageHandlerInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly string $key = '__tuxxedo_csrf_token',
    ) {
    }

    public function clear(): void
    {
        $this->session->set($this->key, null);
    }

    public function get(): ?string
    {
        if (!$this->session->has($this->key)) {
            return null;
        }

        $token = $this->session->getString($this->key);

        return $token !== ''
            ? $token
            : null;
    }

    public function set(
        string $token,
    ): void {
        $this->session->set($this->key, $token);
    }
}
