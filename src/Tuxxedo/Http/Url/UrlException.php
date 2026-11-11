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

namespace Tuxxedo\Http\Url;

use Tuxxedo\Exception;

class UrlException extends Exception
{
    public static function fromInvalidCharacter(
        string $url,
    ): self {
        return new self(
            message: \sprintf(
                'URL "%s" contains whitespace or control characters',
                $url,
            ),
        );
    }

    public static function fromInvalidScheme(
        string $scheme,
    ): self {
        return new self(
            message: \sprintf(
                'URL scheme "%s" is not valid',
                $scheme,
            ),
        );
    }

    public static function fromInvalidHost(
        string $host,
    ): self {
        return new self(
            message: \sprintf(
                'URL host "%s" is not valid',
                $host,
            ),
        );
    }

    public static function fromInvalidPort(
        string $port,
    ): self {
        return new self(
            message: \sprintf(
                'URL port "%s" is not valid, expected 0 to 65535',
                $port,
            ),
        );
    }
}
