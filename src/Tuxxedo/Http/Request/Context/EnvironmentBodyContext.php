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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;

class EnvironmentBodyContext implements BodyContextInterface
{
    public function __construct(
        private readonly SerializerManagerInterface $serializerManager,
        private string $streamInputSource = 'php://input',
        private ?string $contentType = null,
        private ?int $contentLength = null,
    ) {
    }

    public function size(): ?int
    {
        $contentLength = $this->contentLength ?? ($_SERVER['CONTENT_LENGTH'] ?? null);

        if (!\is_string($contentLength) && !\is_int($contentLength)) {
            return null;
        }

        $value = \filter_var($contentLength, \FILTER_VALIDATE_INT);

        if (!\is_int($value) || $value < 0) {
            return null;
        }

        return $value;
    }

    public function getStream(): StreamInterface
    {
        $stream = @\fopen($this->streamInputSource, 'r');

        if ($stream === false) {
            throw HttpException::fromInternalServerError();
        }

        return ResourceStream::fromResource($stream);
    }

    public function getRaw(): string
    {
        return $this->getStream()->getContents();
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

    public function xmlMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): object {
        return $this->serializerManager->xml($skipUnknownKeys)->decode(
            xml: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function xmlMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
    ): array {
        return $this->serializerManager->xml($skipUnknownKeys)->decodeList(
            xml: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    private function mediaType(): ?string
    {
        $contentType = $this->contentType ?? ($_SERVER['CONTENT_TYPE'] ?? null);

        if (!\is_string($contentType)) {
            return null;
        }

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

    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed {
        return \json_decode(
            json: $this->getRaw(),
            associative: $associative,
            flags: $flags | \JSON_THROW_ON_ERROR,
        );
    }

    public function jsonMap(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): object {
        return $this->serializerManager->json(
            skipUnknownKeys: $skipUnknownKeys,
            flags: $flags,
        )->decode(
            json: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }

    public function jsonMapList(
        string $className,
        ?string $key = null,
        ?array $only = null,
        bool $skipUnknownKeys = true,
        int $flags = 0,
    ): array {
        return $this->serializerManager->json(
            skipUnknownKeys: $skipUnknownKeys,
            flags: $flags,
        )->decodeList(
            json: $this->getRaw(),
            className: $className,
            key: $key,
            only: $only,
        );
    }
}
