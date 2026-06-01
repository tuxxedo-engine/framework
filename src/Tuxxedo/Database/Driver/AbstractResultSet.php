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

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Hydrator\HydratableInterface;
use Tuxxedo\Database\Hydrator\HydratorInterface;

abstract class AbstractResultSet implements ResultSetInterface
{
    abstract protected ContainerInterface $container {
        get;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @return \Generator<TClassName>
     */
    public function fetchAll(
        string|\Closure $class = ResultRowInterface::class,
        ?HydratorInterface $hydrator = null,
    ): \Generator {
        for ($i = 0, $numRows = \sizeof($this); $i < $numRows; $i++) {
            yield $this->fetchObject($class, $hydrator);
        }
    }

    public function fetch(): ResultRowInterface
    {
        return $this->fetchObject(ResultRow::class);
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|class-string<TClassName&HydratableInterface>|\Closure(mixed[] $properties): TClassName $class
     * @param mixed[] $properties
     * @return TClassName
     */
    protected function hydrate(
        string|\Closure $class,
        array $properties,
        ?HydratorInterface $hydrator = null,
    ): object {
        if ($class instanceof \Closure) {
            /** @var TClassName */
            return $class($properties);
        }

        $hydrator ??= $this->container->resolve(HydratorInterface::class);

        /** @var array<string, mixed> $properties */
        return $hydrator->hydrate($class, $properties);
    }
}
