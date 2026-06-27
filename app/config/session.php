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

use Tuxxedo\Http\SameSite;
use Tuxxedo\Session\Config\SessionConfig;
use Tuxxedo\Session\Config\SessionConfigInterface;

return static fn (): SessionConfigInterface => new SessionConfig(
    lifetime: 3600,
    path: '/',
    domain: '',
    httpOnly: true,
    secure: false,
    sameSite: SameSite::STRICT,
);
