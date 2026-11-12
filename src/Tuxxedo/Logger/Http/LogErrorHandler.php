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

namespace Tuxxedo\Logger\Http;

use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LoggerInterface;

class LogErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponseInterface {
        $this->logger->log(
            message: 'Unhandled {class}',
            context: [
                'class' => $exception::class,
            ],
            level: $response->responseCode->value >= 500
                ? LogLevel::CRITICAL
                : LogLevel::ERROR,
            exception: $exception,
        );

        return $response;
    }
}
