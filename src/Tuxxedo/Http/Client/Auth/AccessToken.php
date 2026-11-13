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

use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\InstantInterface;

readonly class AccessToken implements AccessTokenInterface
{
    public function __construct(
        #[\SensitiveParameter] public string $accessToken,
        public string $tokenType = 'Bearer',
        public ?InstantInterface $expiresAt = null,
        #[\SensitiveParameter] public ?string $refreshToken = null,
    ) {
    }

    public function isExpired(
        InstantInterface $now,
    ): bool {
        if ($this->expiresAt === null) {
            return false;
        }

        return !$now->plus(Duration::fromSeconds(30))->isBefore($this->expiresAt);
    }
}
