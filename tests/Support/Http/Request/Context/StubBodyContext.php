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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Stream\StreamInterface;

class StubBodyContext implements BodyContextInterface
{
    public function __construct(
        private readonly ?string $contentType = null,
        private readonly ?int $contentLength = null,
    ) {
    }

    public function getStream(): StreamInterface
    {
        throw HttpException::fromInternalServerError();
    }

    public function getRaw(): string
    {
        return '';
    }

    public function size(): ?int
    {
        return $this->contentLength;
    }

    public function isJson(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/json' || \str_ends_with($mediaType, '+json');
    }

    public function isXml(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/xml' ||
            $mediaType === 'text/xml' ||
            \str_ends_with($mediaType, '+xml');
    }

    public function isForm(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return $mediaType === 'application/x-www-form-urlencoded' ||
            $mediaType === 'multipart/form-data';
    }

    public function isText(): bool
    {
        $mediaType = $this->mediaType();

        if ($mediaType === null) {
            return false;
        }

        return \str_starts_with($mediaType, 'text/');
    }

    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed {
        return null;
    }

    public function jsonMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function jsonMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): array {
        throw HttpException::fromInternalServerError();
    }

    public function xmlMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function xmlMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): array {
        throw HttpException::fromInternalServerError();
    }

    private function mediaType(): ?string
    {
        if ($this->contentType === null) {
            return null;
        }

        $contentType = $this->contentType;
        $semicolon = \strpos($contentType, ';');

        if ($semicolon !== false) {
            $contentType = \substr($contentType, 0, $semicolon);
        }

        $contentType = \strtolower(\trim($contentType));

        if ($contentType === '') {
            return null;
        }

        return $contentType;
    }
}
