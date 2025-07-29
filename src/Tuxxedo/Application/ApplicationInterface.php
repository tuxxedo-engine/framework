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

namespace Tuxxedo\Application;

interface ApplicationInterface
{
    /**
     * @param ServiceProviderInterface|(\Closure(): ServiceProviderInterface) $provider
     */
    public function serviceProvider(
        ServiceProviderInterface|\Closure $provider,
    ): static;
}
