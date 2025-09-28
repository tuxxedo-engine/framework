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

abstract class AbstractStatement implements StatementInterface
{
    public private(set) array $bindings = [];

    public function bind(
        string|int $name,
        string|int|float|bool|null $value,
        ?ParameterType $type = null,
    ): void {
        $this->bindings[] = new Binding(
            name: $name,
            value: $value,
            type: $type,
        );
    }

    public function bindValue(
        BindingInterface $binding,
    ): void {
        $this->bindings[] = $binding;
    }

    public function bindAll(
        array $parameters,
    ): void {
        foreach ($parameters as $name => $parameter) {
            if ($parameter instanceof BindingInterface) {
                $this->bindValue($parameter);
            } else {
                $this->bind($name, $parameter);
            }
        }
    }

    public function clearBindings(): void
    {
        $this->bindings = [];
    }
}
