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

use Tuxxedo\Http\HeaderInterface;

// @todo Implement withHeaders() (plural)
interface ResponseInterface extends ResponseCodeInterface
{
    /**
     * @var HeaderInterface[]
     */
    public array $headers {
        get;
    }

    public string $body {
        get;
    }

    public function withHeader(
        HeaderInterface $header,
        bool $replace = false,
    ): static;

    public function withoutHeader(
        string $name,
    ): static;

    public function withResponseCode(
        ResponseCode $responseCode,
    ): static;

    public function withBody(
        string $body,
    ): static;
}
