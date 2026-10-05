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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Session\SessionInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class SessionFunctions
{
    public function __construct(
        private readonly SessionInterface $session,
    ) {
    }

    #[LumiFunction('session')]
    public function session(
        string $name,
    ): mixed {
        return $this->session->raw($name);
    }

    #[LumiFunction('sessionId')]
    public function sessionId(): string
    {
        return $this->session->getIdentifier();
    }

    #[LumiFunction('hasSession')]
    public function hasSession(
        string $name,
    ): bool {
        return $this->session->has($name);
    }
}
