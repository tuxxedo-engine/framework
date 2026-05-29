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
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Mapper\MapperException;
use Tuxxedo\Mapper\MapperInterface;

class EnvironmentBodyContext implements BodyContextInterface
{
    public function __construct(
        private string $streamInputSource = 'php://input',
        private ?string $contentType = null,
        private MapperInterface $mapper = new Mapper(),
    ) {
    }

    public function getStream()
    {
        $stream = @\fopen($this->streamInputSource, 'r');

        if ($stream === false) {
            throw HttpException::fromInternalServerError();
        }

        return $stream;
    }

    public function getRaw(): string
    {
        return \stream_get_contents($this->getStream());
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

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     *
     * @throws HttpException
     * @throws MapperException
     * @throws \JsonException
     */
    public function jsonMapTo(
        string|object $className,
        int $flags = 0,
    ): object {
        $value = $this->getJson(
            flags: $flags,
        );

        if (\is_array($value)) {
            return $this->mapper->mapArrayTo(
                input: $value,
                className: $className,
                skipInvalidProperties: true,
                castType: true,
            );
        }

        if (\is_object($value)) {
            return $this->mapper->mapObjectTo(
                input: $value,
                className: $className,
                skipInvalidProperties: true,
                castType: true,
            );
        }

        throw HttpException::fromInternalServerError();
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]
     *
     * @throws HttpException
     * @throws MapperException
     * @throws \JsonException
     */
    public function jsonMapToArrayOf(
        string|object $className,
        int $flags = 0,
    ): array {
        $value = $this->getJson(
            flags: $flags,
        );

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $this->mapper->mapToArrayOf(
            input: $value,
            className: $className,
            skipInvalidProperties: true,
            castType: true,
        );
    }
}
