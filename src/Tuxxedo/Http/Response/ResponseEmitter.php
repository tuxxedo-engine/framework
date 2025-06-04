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

class ResponseEmitter implements ResponseEmitterInterface
{
    private bool $sent = false;

    public function emit(
        ResponseInterface $response,
        bool $sendHeaders = true,
    ): void {
        $maxLength = null;

        if ($sendHeaders) {
            \http_response_code(
                response_code: $response->responseCode->getStatusCode(),
            );

            foreach ($response->headers as $header) {
                if ($header->name === 'Content-Length') {
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

        if ($maxLength !== null) {
            echo \mb_substr($response->body, 0, $maxLength);
        } else {
            echo $response->body;
        }
    }

    // @todo Investigate whether ResponseInterface also needs to have such a check, but it is immutable
    public function isSent(): bool
    {
        return $this->sent;
    }
}
