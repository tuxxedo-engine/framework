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

namespace Tuxxedo\Database\Builder;

use Tuxxedo\Database\Driver\HydratableInterface;

class SelectBuilder extends AbstractWhereBuilder implements SelectBuilderInterface
{
    protected function generateSql(): string
    {
        // @todo Implement
        // @todo Call parent

        return '';
    }

    public function select(
        string ...$columns,
    ): static {
        // @todo Implement

        return $this;
    }

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        // @todo Implement

        return $this;
    }

    public function groupBy(
        string ...$columns,
    ): static {
        // @todo Implement

        return $this;
    }

    public function limit(
        int $limit,
    ): static {
        // @todo Implement

        return $this;
    }

    public function offset(
        int $offset,
    ): static {
        // @todo Implement

        return $this;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return TClassName|null
     */
    public function fetch(
        string|\Closure $class,
    ): ?object {
        $result = $this->execute();

        if ($result->count() > 0) {
            return $result->fetchObject($class);
        }

        return null;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<TClassName>
     */
    public function fetchAll(
        string|\Closure $class,
    ): \Generator {
        yield from $this->execute()->fetchAll($class);
    }
}
