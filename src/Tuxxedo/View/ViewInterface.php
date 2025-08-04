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

namespace Tuxxedo\View;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Response\ResponseInterface;

interface ViewInterface
{
    public string $name {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $scope {
        get;
    }

    public function toResponse(
        ContainerInterface $container,
    ): ResponseInterface;
}
