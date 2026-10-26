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

namespace Support\Http\Kernel;

use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class RecordingErrorHandler implements ErrorHandlerInterface
{
    public int $callCount = 0;

    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponseInterface {
        $this->callCount++;

        return $response;
    }
}
