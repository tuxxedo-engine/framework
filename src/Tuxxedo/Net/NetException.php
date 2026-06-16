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

namespace Tuxxedo\Net;

class NetException extends \Exception
{
    public static function fromUnparseableCidrAddress(
        string $cidr,
    ): self {
        return new self(
            message: \sprintf(
                'CIDR has unparseable address: "%s"',
                $cidr,
            ),
        );
    }

    public static function fromNonNumericCidrPrefix(
        string $cidr,
    ): self {
        return new self(
            message: \sprintf(
                'CIDR has non-numeric prefix: "%s"',
                $cidr,
            ),
        );
    }

    public static function fromCidrPrefixOutOfRange(
        string $cidr,
    ): self {
        return new self(
            message: \sprintf(
                'CIDR prefix exceeds address family width: "%s"',
                $cidr,
            ),
        );
    }

    public static function fromUnresolvableHostname(
        string $hostname,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot resolve hostname "%s"',
                $hostname,
            ),
        );
    }
}
