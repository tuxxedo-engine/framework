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

namespace Tuxxedo\Database\Config;

use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('database.manager')]
readonly class ConnectionManagerConfig implements ConnectionManagerConfigInterface
{
    /**
     * @param list<ConnectionConfigInterface> $connections
     */
    public function __construct(
        public array $connections = [],
    ) {
    }
}
