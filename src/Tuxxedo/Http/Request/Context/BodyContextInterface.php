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
use Tuxxedo\Mapper\MapperException;

interface BodyContextInterface
{
    /**
     * @return resource
     *
     * @throws HttpException
     */
    public function getStream();

    public function getRaw(): string;

    /**
     * @throws \JsonException
     */
    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed;

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
    ): object;

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
    ): array;
}
