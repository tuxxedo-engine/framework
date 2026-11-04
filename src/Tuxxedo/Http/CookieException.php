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

namespace Tuxxedo\Http;

use Tuxxedo\Exception;

class CookieException extends Exception
{
    public static function fromSameSiteNoneRequiresSecure(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Cookie "%s" uses SameSite=None, which browsers only accept together with Secure',
                $name,
            ),
        );
    }
}
