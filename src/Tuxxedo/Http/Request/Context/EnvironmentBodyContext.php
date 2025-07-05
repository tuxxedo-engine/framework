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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Mapper\MapperException;
use Tuxxedo\Mapper\MapperInterface;

class EnvironmentBodyContext implements BodyContextInterface
{
    public function __construct(
        private MapperInterface $mapper,
    ) {
    }

    public function getStream()
    {
        $stream = @\fopen('php://input', 'r');

        if ($stream === false) {
            throw HttpException::fromInternalServerError();
        }

        return $stream;
    }

    public function getRaw(): string
    {
        $contents = \stream_get_contents($this->getStream());

        if ($contents === false) {
            throw HttpException::fromInternalServerError();
        }

        return $contents;
    }

    public function getJson(): mixed
    {
        return \json_decode(
            json: $this->getRaw(),
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $class
     * @return TClassName
     *
     * @throws HttpException
     * @throws \JsonException
     * @throws MapperException
     */
    public function mapJsonTo(
        string|object $class,
    ): object {
        $value = $this->getJson();

        if (\is_array($value)) {
            return $this->mapper->mapArrayTo($value, $class);
        }

        if (\is_object($value)) {
            return $this->mapper->mapObjectTo($value, $class);
        }

        throw HttpException::fromInternalServerError();
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $class
     * @return TClassName[]
     *
     * @throws HttpException
     * @throws \JsonException
     * @throws MapperException
     */
    public function mapJsonToArrayOf(
        string|object $class,
    ): array {
        $value = $this->getJson();

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $this->mapper->mapToArrayOf($value, $class);
    }
}
