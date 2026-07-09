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

namespace Tuxxedo\Security\Jwt;

interface TokenInterface
{
    public HeaderInterface $header {
        get;
    }

    public ClaimsInterface $claims {
        get;
    }

    public string $signature {
        get;
    }

    public string $compact {
        get;
    }
}
