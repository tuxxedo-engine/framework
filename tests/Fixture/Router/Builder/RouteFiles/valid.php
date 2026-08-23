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

use Fixture\Router\Builder\SimpleController;
use Tuxxedo\Router\Builder\RouteBuilderInterface;

return static fn (RouteBuilderInterface $builder): array => $builder
    ->get(
        uri: '/',
        controller: SimpleController::class,
        action: 'home',
        name: 'home',
    )
    ->get(
        uri: '/about',
        controller: SimpleController::class,
        action: 'about',
        name: 'about',
    )
    ->build();
