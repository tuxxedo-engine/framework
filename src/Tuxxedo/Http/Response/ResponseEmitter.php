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

namespace Tuxxedo\Http\Response;

use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Response\Stream\StreamInterface;

class ResponseEmitter implements ResponseEmitterInterface
{
    public private(set) bool $sent = false;

    public function emit(
        ResponseInterface|ResponseExceptionInterface $response,
        bool $sendHeaders = true,
    ): void {
        if ($response instanceof ResponseExceptionInterface) {
            $response = $response->toResponse();
        }

        $maxLength = null;

        if (!$this->sent && $sendHeaders) {
            foreach ($response->headers as $header) {
                if ($header instanceof CookieInterface) {
                    \setcookie(
                        name: $header->name,
                        value: $header->value,
                        expires_or_options: $header->expires,
                        path: $header->path,
                        domain: $header->domain,
                        secure: $header->secure,
                        httponly: $header->httpOnly,
                    );

                    continue;
                } elseif ($header->is('Content-Length')) {
                    $maxLength = (int) $header->value;
                }

                \header(
                    \sprintf(
                        '%s: %s',
                        $header->name,
                        $header->value,
                    ),
                );
            }

            \header(
                \sprintf(
                    '%s %d %s',
                    $response->httpVersion->value,
                    $response->responseCode->getStatusCode(),
                    $response->responseCode->getStatusText(),
                ),
                true,
                $response->responseCode->getStatusCode(),
            );

            $this->sent = true;
        }

        if ($response->body instanceof StreamInterface) {
            if (($length = $response->body->getSize()) !== null) {
                \header(
                    \sprintf(
                        'Content-Length: %d',
                        $length,
                    ),
                );
            }

            $currentOutputSize = 0;

            while (!$response->body->eof()) {
                $chunk = $response->body->read();

                if ($chunk === null) {
                    break;
                }

                if ($maxLength !== null) {
                    $currentOutputSize += \strlen($chunk);

                    if ($currentOutputSize >= $maxLength) {
                        $chunk = \substr($chunk, 0, \strlen($chunk) - ($currentOutputSize - $maxLength));
                    }
                }

                echo $chunk;

                if ($response->body->autoFlush) {
                    \flush();
                }

                if ($maxLength !== null && $currentOutputSize === $maxLength) {
                    break;
                }

                if (\connection_aborted() === 1) {
                    break; // @codeCoverageIgnore
                }
            }

            $response->body->close();
        } else {
            if ($maxLength !== null && $maxLength > -1) {
                echo \substr($response->body, 0, $maxLength);
            } else {
                echo $response->body;
            }
        }
    }
}
