<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

namespace App\Subscribers\Events;

use App\Models\User;

readonly class UserCreatedEvent
{
    public function __construct(
        public \DateTimeImmutable $createdAt,
        public User $model,
    ) {
    }
}
