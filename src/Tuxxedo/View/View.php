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
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

readonly class View implements ViewInterface
{
    /**
     * @param array<string, mixed> $scope
     */
    public function __construct(
        public string $name,
        public array $scope = [],
    ) {
    }

    public function toResponse(
        ContainerInterface $container,
    ): ResponseInterface {
        return new Response(
            body: $container->resolve(ViewRenderInterface::class)->render($this),
        );
    }
}
