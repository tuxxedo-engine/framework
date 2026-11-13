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

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\BodyContextInterface;

readonly class CsvDecoder implements ResponseDecoderInterface
{
    public function __construct(
        public string $separator = ',',
        public string $enclosure = '"',
    ) {
    }

    public function supports(
        BodyContextInterface $body,
    ): bool {
        return $body->isCsv();
    }

    public function map(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function mapList(
        BodyContextInterface $body,
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): array {
        return $body->csvMapList(
            className: $className,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            separator: $this->separator,
            enclosure: $this->enclosure,
        );
    }
}
