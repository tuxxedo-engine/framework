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

namespace Support\Http\Response;

use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class StubResponseEmitter implements ResponseEmitterInterface
{
    public ?ResponseInterface $lastResponse = null;
    public private(set) bool $sent = false;

    public function emit(
        ResponseInterface|ResponseExceptionInterface $response,
        bool $sendHeaders = true,
    ): void {
        if ($response instanceof ResponseExceptionInterface) {
            $response = $response->toResponse();
        }

        $this->lastResponse = $response;
        $this->sent = true;
    }
}
