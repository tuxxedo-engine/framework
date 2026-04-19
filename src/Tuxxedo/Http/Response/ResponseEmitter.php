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
    private bool $sent = false;

    public function emit(
        ResponseInterface|ResponseExceptionInterface $response,
        bool $sendHeaders = true,
    ): void {
        if ($response instanceof ResponseExceptionInterface) {
            $response = $response->toResponse();
        }

        $maxLength = null;

        if (!$this->sent && $sendHeaders) {
            \http_response_code(
                response_code: $response->responseCode->getStatusCode(),
            );

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
                } elseif ($header->name === 'Content-Length') {
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
                    $currentOutputSize += \mb_strlen($chunk);

                    if ($currentOutputSize >= $maxLength) {
                        $chunk = \mb_substr($chunk, 0, $currentOutputSize - $maxLength);
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
                    break;
                }
            }

            $response->body->close();
        } else {
            if ($maxLength !== null && $maxLength > -1) {
                echo \mb_substr($response->body, 0, $maxLength);
            } else {
                echo $response->body;
            }
        }
    }

    public function isSent(): bool
    {
        return $this->sent;
    }
}
