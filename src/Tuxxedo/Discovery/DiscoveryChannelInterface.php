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

namespace Tuxxedo\Discovery;

// @todo Move this to CLI once ready
interface DiscoveryChannelInterface
{
    /**
     * @return DiscoveryType[]
     */
    public function provides(): array;

    /**
     * @return array<class-string>
     */
    public function discover(DiscoveryType $type): array;
}
