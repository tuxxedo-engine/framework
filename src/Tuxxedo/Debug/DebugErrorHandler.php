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

namespace Tuxxedo\Debug;

use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class DebugErrorHandler implements ErrorHandlerInterface
{
    public static function registerPhpErrorHandler(): void
    {
        \set_error_handler(
            static fn (int $errno, string $errstr, ?string $errfile, ?int $errline): never => throw new \ErrorException(
                message: $errstr,
                severity: $errno,
                filename: $errfile ?? '',
                line: $errline ?? 0,
            ),
        );
    }

    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponseInterface {
        if ($exception->getFile() === '') {
            $location = 'unknown:' . \strval($exception->getLine());
        } else {
            $location = $exception->getFile() . ':' . \strval($exception->getLine());
        }

        $html = '';
        $html .= '<h1>Tuxxedo Engine Debugger</h1>';
        $html .= 'Exception: ' . $exception::class . '<br>';
        $html .= 'Message: ' . \htmlspecialchars($exception->getMessage()) . '<br>';
        $html .= 'File: ' . $location;

        return $response->withBody($html);
    }
}
