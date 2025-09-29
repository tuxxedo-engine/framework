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

namespace Tuxxedo\Database\Driver;

// @todo This needs to support placeholder normalization
interface StatementInterface
{
    public ConnectionInterface $connection {
        get;
    }

    public string $sql {
        get;
    }

    /**
     * @var array<string|int, BindingInterface>
     */
    public array $bindings {
        get;
    }

    public function bind(
        string|int $name,
        string|int|float|bool|null $value,
        ?ParameterType $type = null,
    ): void;

    public function bindValue(
        BindingInterface $binding,
    ): void;

    /**
     * @param array<(string|int|float|bool|null)|BindingInterface> $parameters
     */
    public function bindAll(
        array $parameters,
    ): void;

    public function clearBindings(): void;

    /**
     * @param array<string|int|float|bool|null> $parameters
     */
    public function execute(
        array $parameters = [],
    ): ResultSetInterface;
}
