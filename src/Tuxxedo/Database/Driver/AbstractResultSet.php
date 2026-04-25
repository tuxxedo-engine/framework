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
use Tuxxedo\Reflection\ClassReflector;

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
    ): \Generator {
        for ($i = 0, $numRows = \sizeof($this); $i < $numRows; $i++) {
            yield $this->fetchObject($class);
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
    ): object {
        if (!$class instanceof \Closure) {
            if (\in_array(HydratableInterface::class, ($interfaces = \class_implements($class)) !== false ? $interfaces : [], true)) {
                /** @var class-string<TClassName&HydratableInterface> $class */
                $class = $this->container->resolveName($class);

                $class = $class::create(...);
            } else {
                $class = function (array $properties) use ($class): object {
                    $model = $this->container->resolve($class);
                    $reflector = ClassReflector::createFromObject($model);

                    foreach ($properties as $column => $value) {
                        $reflector->property($column)->setValue($model, $value);
                    }

                    return $model;
                };
            }
        }

        /** @var \Closure(): TClassName $class */
        /** @var TClassName */
        return $this->container->call(
            $class,
            [
                'properties' => $properties,
            ],
        );
    }
}
