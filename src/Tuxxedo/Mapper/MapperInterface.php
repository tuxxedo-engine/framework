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
     * @template TClassName of object
     *
     * @param array<mixed> $input
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     *
     * @throws MapperException
     */
    public function mapArrayTo(
        array $input,
        string|object $className,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     *
     * @throws MapperException
     */
    public function mapObjectTo(
        object $input,
        string|object $className,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $input
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]
     *
     * @throws MapperException
     */
    public function mapToArrayOf(
        array $input,
        string|object $className,
    ): array;
}
