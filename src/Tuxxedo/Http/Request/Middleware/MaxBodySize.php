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

namespace Tuxxedo\Http\Request\Middleware;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class MaxBodySize implements MiddlewareInterface
{
    public function __construct(
        public int $maxBytes,
        public bool $verifyActualSize = false,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if ($request->headers->has('Content-Length')) {
            $contentLength = $request->headers->int('Content-Length');

            if ($contentLength > $this->maxBytes) {
                throw HttpException::fromPayloadTooLarge();
            }
        }

        if ($this->verifyActualSize) {
            $this->enforceActualSize($request);
        }

        return $next->handle($request, $next);
    }

    /**
     * @throws HttpException
     */
    private function enforceActualSize(
        RequestInterface $request,
    ): void {
        $bytesRead = 0;
        $stream = $request->body->getStream();

        while (!\feof($stream)) {
            $chunk = \fread($stream, 8192);

            if ($chunk === false) {
                \fclose($stream); // @codeCoverageIgnore

                throw HttpException::fromInternalServerError(); // @codeCoverageIgnore
            }

            $bytesRead += \strlen($chunk);

            if ($bytesRead > $this->maxBytes) {
                \fclose($stream);

                throw HttpException::fromPayloadTooLarge();
            }
        }

        \fclose($stream);
    }
}
