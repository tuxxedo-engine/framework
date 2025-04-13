<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Net;

class IpAddress implements IpAddressInterface
{
    final public function __construct(
        public private(set) string $address,
        public private(set) IpAddressKind $kind,
    ) {
    }

    public function create(string $address): static
    {
        // @todo Use dedicated validator API for v4
        // @todo Use dedicated validator API for v5
        // @todo Use dedicated validator Exception
        return match (true) {
            \filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) => new static(
                address: $address,
                kind: IpAddressKind::V4,
            ),
            \filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) => new static(
                address: $address,
                kind: IpAddressKind::V6,
            ),
            default => throw new \ValueError('Invalid ip address format'),
        };
    }
}
