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

namespace App\ErrorHandlers;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\View\View;

class HttpErrorHandler implements ErrorHandlerInterface
{
    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponsableInterface {
        /** @var HttpException $exception */

        return new View(
            match ($exception->responseCode) {
                ResponseCode::NOT_FOUND => 'errors/not_found',
                default => 'errors/generic',
            },
            [
                'exception' => $exception,
            ],
        );
    }
}
