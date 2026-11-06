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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

use Tuxxedo\View\Lumi\Library\Attribute\Context;

readonly class CallableParameter implements CallableParameterInterface
{
    public function __construct(
        public string $name,
        public int $position,
        public bool $optional = false,
        public bool $variadic = false,
    ) {
    }

    /**
     * @return list<CallableParameterInterface>
     */
    public static function listFromReflection(
        \ReflectionFunctionAbstract $function,
    ): array {
        $parameters = [];

        foreach ($function->getParameters() as $parameter) {
            if ($parameter->getAttributes(Context::class) !== []) {
                continue;
            }

            $parameters[] = new self(
                name: $parameter->getName(),
                position: \sizeof($parameters),
                optional: $parameter->isOptional(),
                variadic: $parameter->isVariadic(),
            );
        }

        return $parameters;
    }
}
