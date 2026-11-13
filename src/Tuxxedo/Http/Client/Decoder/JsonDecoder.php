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

namespace Tuxxedo\Http\Client\Decoder;

use Tuxxedo\Http\Request\Context\BodyContextInterface;

readonly class JsonDecoder implements ResponseDecoderInterface
{
    public function supports(
        BodyContextInterface $body,
    ): bool {
        return $body->isJson();
    }

    public function map(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): object {
        return $body->jsonMap(
            className: $className,
            key: $key,
            only: $only,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
        );
    }

    public function mapList(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): array {
        return $body->jsonMapList(
            className: $className,
            key: $key,
            only: $only,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
        );
    }
}
