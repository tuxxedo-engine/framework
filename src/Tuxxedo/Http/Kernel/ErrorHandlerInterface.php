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

namespace Tuxxedo\Http\Kernel;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseInterface;

interface ErrorHandlerInterface
{
    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponsableInterface|ResponseInterface;
}
