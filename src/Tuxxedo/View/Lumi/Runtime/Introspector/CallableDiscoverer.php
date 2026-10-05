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

class CallableDiscoverer implements CallableDiscovererInterface
{
    public function discoverFilters(
        string $className,
    ): array {
        $metadata = [];

        foreach ((new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(LumiFilter::class) as $attribute) {
                $instance = $attribute->newInstance();
                $index = self::findContextParameterIndex($method);

                $metadata[] = new CallableMetadata(
                    name: $instance->name,
                    kind: CallableKind::TYPED_ATTRIBUTE,
                    className: $className,
                    methodName: $method->getName(),
                    wantsContext: $index !== null,
                    contextParameterIndex: $index,
                    aliases: \array_values($instance->aliases),
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
}
