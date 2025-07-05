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

interface BodyContextInterface
{
    /**
     * @return resource
     *
     * @throws MapperException
     */
    public function getStream();

    /**
     * @throws \JsonException
     * @throws MapperException
     */
    public function getRaw(): string;

    /**
     * @throws \JsonException
     */
    public function getJson(): mixed;

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
    ): object;

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
    ): array;
}
