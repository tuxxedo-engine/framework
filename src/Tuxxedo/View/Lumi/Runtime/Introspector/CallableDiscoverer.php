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
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class CallableDiscoverer implements CallableDiscovererInterface
{
    public static function discoverFunctions(
        string $className,
    ): array {
        $metadata = [];

        foreach ((new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(LumiFunction::class) as $attribute) {
                $instance = $attribute->newInstance();
                $index = self::findContextParameterIndex($method);

                $metadata[] = new CallableMetadata(
                    name: $instance->name,
                    className: $className,
                    methodName: $method->getName(),
                    wantsContext: $index !== null,
                    contextParameterIndex: $index,
                    aliases: \array_values($instance->aliases),
                    minimumArguments: self::countMinimumArguments($method),
                    maximumArguments: self::countMaximumArguments($method),
                );
            }
        }

        return $metadata;
    }

    public static function discoverFilters(
        string $className,
    ): array {
        $metadata = [];

        foreach ((new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(LumiFilter::class) as $attribute) {
                $instance = $attribute->newInstance();
                $index = self::findContextParameterIndex($method);
                $maximumArguments = self::countMaximumArguments($method);

                if ($maximumArguments === 0) {
                    throw IntrospectorException::fromFilterWithoutValueParameter(
                        name: $instance->name,
                        className: $className,
                        methodName: $method->getName(),
                    );
                }

                $metadata[] = new CallableMetadata(
                    name: $instance->name,
                    className: $className,
                    methodName: $method->getName(),
                    wantsContext: $index !== null,
                    contextParameterIndex: $index,
                    aliases: \array_values($instance->aliases),
                    minimumArguments: self::countMinimumArguments($method),
                    maximumArguments: $maximumArguments,
                );
            }
        }

        return $metadata;
    }

    private static function findContextParameterIndex(
        \ReflectionMethod $method,
    ): ?int {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getAttributes(Context::class) !== []) {
                return $parameter->getPosition();
            }
        }

        return null;
    }

    private static function countMinimumArguments(
        \ReflectionMethod $method,
    ): int {
        $count = 0;

        foreach ($method->getParameters() as $parameter) {
            if (
                !$parameter->isOptional() &&
                $parameter->getAttributes(Context::class) === []
            ) {
                $count++;
            }
        }

        return $count;
    }

    private static function countMaximumArguments(
        \ReflectionMethod $method,
    ): ?int {
        if ($method->isVariadic()) {
            return null;
        }

        $count = 0;

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getAttributes(Context::class) === []) {
                $count++;
            }
        }

        return $count;
    }
}
