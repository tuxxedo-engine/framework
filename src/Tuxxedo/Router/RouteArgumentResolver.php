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

namespace Tuxxedo\Router;

use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Attribute\ArgumentConsumerInterface;
use Tuxxedo\Router\Compiler\PathCompilerInterface;

class RouteArgumentResolver implements RouteArgumentResolverInterface
{
    public function __construct(
        private readonly PathCompilerInterface $pathCompiler,
    ) {
    }

    public function resolve(
        string $path,
        ?PrefixInterface $prefix,
        \ReflectionMethod $method,
    ): ResolvedPathInterface {
        $compiled = $this->pathCompiler->compile(
            path: $path,
            prefix: $prefix,
        );

        if (\sizeof($compiled->argumentNodes) === 0) {
            return new ResolvedPath(
                regexPath: null,
                arguments: [],
                stickyPrefix: null,
            );
        }

        $names = \array_map(
            static fn (ArgumentNode $node): string => $node->name,
            $compiled->argumentNodes,
        );

        if (\sizeof($names) !== \sizeof(\array_unique($names))) {
            throw RouterException::fromNotAllArgumentNamesAreUnique(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                names: $names,
            );
        }

        $arguments = [];
        $enumArguments = [];

        foreach ($compiled->argumentNodes as $node) {
            $argument = $this->argument(
                node: $node,
                method: $method,
                prefix: $prefix,
            );

            if ($argument->enumClass !== null) {
                $enumArguments[$node->name] = $argument->enumClass;
            }

            $arguments[] = $argument;
        }

        $regexPath = $compiled->regexPath;

        if (\sizeof($enumArguments) > 0) {
            $regexPath = $this->pathCompiler->compile(
                path: $path,
                prefix: $prefix,
                enumArguments: $enumArguments,
            )->regexPath;
        }

        return new ResolvedPath(
            regexPath: $regexPath,
            arguments: $arguments,
            stickyPrefix: $prefix instanceof StickyPrefixInterface
                ? $prefix::class
                : null,
        );
    }

    /**
     * @throws RouterException
     */
    private function argument(
        ArgumentNode $node,
        \ReflectionMethod $method,
        ?PrefixInterface $prefix,
    ): RouteArgumentInterface {
        $parameter = self::findParameter(
            method: $method,
            name: $node->name,
        );

        if ($parameter === null) {
            $consumers = self::consumers(
                method: $method,
                name: $node->name,
            );

            if ($consumers > 1) {
                throw RouterException::fromRouteArgumentClaimedTwice(
                    className: $method->getDeclaringClass()->getName(),
                    method: $method->getName(),
                    argument: $node->name,
                );
            }

            if ($consumers === 1) {
                return new RouteArgument(
                    node: $node,
                    mappedName: null,
                    nativeType: 'string',
                    allowsNull: false,
                    defaultValue: null,
                    resolverConsumed: true,
                );
            }

            if ($node->prefixed) {
                $defaultValue = $prefix instanceof PrefixDefaultsInterface
                    ? $prefix->getDefaultValue($node->name)
                    : null;

                return new RouteArgument(
                    node: $node,
                    mappedName: null,
                    nativeType: \get_debug_type($defaultValue),
                    allowsNull: $defaultValue === null,
                    defaultValue: $defaultValue,
                    resolverConsumed: true,
                );
            }

            throw RouterException::fromArgumentHasNoMatchingParameter(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                argument: $node->name,
            );
        }

        if (
            $node->optional &&
            !$parameter->isDefaultValueAvailable() &&
            !($node->prefixed && $prefix instanceof PrefixDefaultsInterface)
        ) {
            throw RouterException::fromOptionalArgumentHasNoDefaultValue(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                parameter: $parameter->getName(),
            );
        }

        $allowsNull = false;
        $enumClass = null;
        $nativeType = self::nativeType(
            method: $method,
            parameter: $parameter,
            allowsNull: $allowsNull,
            enumClass: $enumClass,
        );

        $defaultValue = null;

        if ($parameter->isDefaultValueAvailable()) {
            $defaultValue = $parameter->getDefaultValue();
        } elseif ($node->prefixed && $prefix instanceof PrefixDefaultsInterface) {
            $defaultValue = $prefix->getDefaultValue($node->name);
        }

        return new RouteArgument(
            node: $node,
            mappedName: $parameter->getName() !== $node->name
                ? $parameter->getName()
                : null,
            nativeType: $nativeType,
            allowsNull: $allowsNull,
            defaultValue: $defaultValue,
            enumClass: $enumClass,
        );
    }

    private static function findParameter(
        \ReflectionMethod $method,
        string $name,
    ): ?\ReflectionParameter {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }

            $attributes = $parameter->getAttributes(
                name: Argument::class,
                flags: \ReflectionAttribute::IS_INSTANCEOF,
            );

            if (\sizeof($attributes) === 0) {
                continue;
            }

            /** @var Argument $instance */
            $instance = $attributes[0]->newInstance();

            if ($instance->label === $name) {
                return $parameter;
            }
        }

        return null;
    }

    private static function consumers(
        \ReflectionMethod $method,
        string $name,
    ): int {
        $count = 0;

        foreach ($method->getParameters() as $parameter) {
            $attributes = $parameter->getAttributes(
                name: ArgumentConsumerInterface::class,
                flags: \ReflectionAttribute::IS_INSTANCEOF,
            );

            foreach ($attributes as $attribute) {
                /** @var ArgumentConsumerInterface $instance */
                $instance = $attribute->newInstance();

                if (\in_array($name, $instance->routeArguments, true)) {
                    ++$count;

                    break;
                }
            }
        }

        return $count;
    }

    /**
     * @param-out class-string<\UnitEnum>|null $enumClass
     *
     * @throws RouterException
     */
    private static function nativeType(
        \ReflectionMethod $method,
        \ReflectionParameter $parameter,
        bool &$allowsNull,
        ?string &$enumClass,
    ): string {
        $enumClass = null;
        $type = $parameter->getType();

        if ($type === null) {
            throw RouterException::fromHasNoType(
                className: $method->getDeclaringClass()->getName(),
                method: $method->getName(),
                parameter: $parameter->getName(),
            );
        }

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            if ($type->getName() === 'object' || $type->getName() === 'array') {
                throw RouterException::fromUnsupportedNativeType(
                    className: $method->getDeclaringClass()->getName(),
                    method: $method->getName(),
                    parameter: $parameter->getName(),
                    type: $type->getName(),
                );
            }

            $allowsNull = $type->allowsNull();

            return $type->getName();
        }

        $typeName = $type instanceof \ReflectionNamedType
            ? $type->getName()
            : null;

        if ($typeName !== null && \is_subclass_of($typeName, \UnitEnum::class)) {
            $allowsNull = $type->allowsNull();
            $enumClass = $typeName;

            return $enumClass;
        }

        if ($type->allowsNull()) {
            return 'null';
        }

        throw RouterException::fromUnsupportedType(
            className: $method->getDeclaringClass()->getName(),
            method: $method->getName(),
            parameter: $parameter->getName(),
        );
    }
}
