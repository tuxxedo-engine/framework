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

namespace Tuxxedo\Http\Client\Auth;

use Tuxxedo\Temporal\InstantInterface;

interface AccessTokenInterface
{
    public string $accessToken {
        get;
    }

    public string $tokenType {
        get;
    }

    public ?InstantInterface $expiresAt {
        get;
    }

    public ?string $refreshToken {
        get;
    }

    public function isExpired(
        InstantInterface $now,
    ): bool;
}
