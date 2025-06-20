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

namespace Tuxxedo\Mapper;

interface MapperInterface
{
    /**
     * @template T of object
     *
     * @param array<mixed> $input
     * @param class-string<T>|T $className
     * @return T
     *
     * @throws MapperException
     */
    public function mapArrayTo(
        array $input,
        string|object $className,
    ): object;

    /**
     * @template T of object
     *
     * @param class-string<T>|T $className
     * @return T
     *
     * @throws MapperException
     */
    public function mapObjectTo(
        object $input,
        string|object $className,
    ): object;

    /**
     * @template T of object
     *
     * @param array<array<mixed>|object> $input
     * @param class-string<T>|T $className
     * @return T[]
     *
     * @throws MapperException
     */
    public function mapToArrayOf(
        array $input,
        string|object $className,
    ): array;
}
