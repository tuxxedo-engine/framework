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

namespace Tuxxedo\Http\Response;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;

interface ResponsableInterface
{
    /**
     * @throws HttpException
     */
    public function toResponse(
        ContainerInterface $container,
    ): ResponseInterface;
}
