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

namespace Tuxxedo\Console\Invocation;

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\CommandParameterInterface;
use Tuxxedo\Console\Attribute\Flag;
use Tuxxedo\Console\Attribute\Option;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Container\ContainerInterface;

class ParameterBinder implements ParameterBinderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function bind(
        ParsedArgvInterface $argv,
        CommandDescriptorInterface $descriptor,
    ): array {
        $method = new \ReflectionMethod(
            $descriptor->className,
            $descriptor->methodName,
        );

        $bound = [];
        $argumentPosition = 0;

        foreach ($method->getParameters() as $parameter) {
            $bindings = $parameter->getAttributes(
                CommandParameterInterface::class,
                \ReflectionAttribute::IS_INSTANCEOF,
            );

            if ($bindings === []) {
                $bound[] = $this->resolveFromContainer(
                    parameter: $parameter,
                    className: $descriptor->className,
                    methodName: $descriptor->methodName,
                );

                continue;
            }

            $binding = $bindings[0]->newInstance();
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                throw ConsoleException::fromMissingCommandParameterType(
                    className: $descriptor->className,
                    methodName: $descriptor->methodName,
                    parameterName: $parameter->getName(),
                );
            }

            $name = $binding->name ?? $parameter->getName();

            if ($binding instanceof Argument) {
                if ($parameter->isVariadic()) {
                    for ($p = $argumentPosition; $p < \sizeof($argv->positionals); $p++) {
                        $bound[] = $this->coerce(
                            rawValue: $argv->positionals[$p],
                            typeName: $type->getName(),
                            isBuiltin: $type->isBuiltin(),
                            parameterName: $name,
                        );
                    }

                    continue;
                }

                if (isset($argv->positionals[$argumentPosition])) {
                    $bound[] = $this->coerce(
                        rawValue: $argv->positionals[$argumentPosition],
                        typeName: $type->getName(),
                        isBuiltin: $type->isBuiltin(),
                        parameterName: $name,
                    );
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $bound[] = $parameter->getDefaultValue();
                } else {
                    throw ConsoleException::fromMissingCommandArgument($name);
                }

                $argumentPosition++;

                continue;
            }

            if ($binding instanceof Option) {
                if (!isset($argv->options[$name])) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $bound[] = $parameter->getDefaultValue();
                    } else {
                        throw ConsoleException::fromMissingRequiredOption($name);
                    }

                    continue;
                }

                $values = $argv->options[$name];

                if ($binding->repeatable) {
                    $coerced = [];

                    foreach ($values as $value) {
                        $coerced[] = $this->coerce(
                            rawValue: $value,
                            typeName: $type->getName(),
                            isBuiltin: $type->isBuiltin(),
                            parameterName: $name,
                        );
                    }

                    $bound[] = $coerced;
                } else {
                    $bound[] = $this->coerce(
                        rawValue: $values[0],
                        typeName: $type->getName(),
                        isBuiltin: $type->isBuiltin(),
                        parameterName: $name,
                    );
                }

                continue;
            }

            /** @var Flag $binding */
            $bound[] = $argv->flags[$name] ?? false;
        }

        return $bound;
    }

    /**
     * @param class-string $className
     */
    private function resolveFromContainer(
        \ReflectionParameter $parameter,
        string $className,
        string $methodName,
    ): mixed {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw ConsoleException::fromMissingCommandParameterType(
                className: $className,
                methodName: $methodName,
                parameterName: $parameter->getName(),
            );
        }

        if ($type->isBuiltin()) {
            throw ConsoleException::fromUnsupportedCommandParameterType(
                parameterName: $parameter->getName(),
                typeName: $type->getName(),
            );
        }

        /** @var class-string $classNameType */
        $classNameType = $type->getName();

        return $this->container->resolve($classNameType);
    }

    private function coerce(
        string $rawValue,
        string $typeName,
        bool $isBuiltin,
        string $parameterName,
    ): mixed {
        if ($isBuiltin) {
            return match ($typeName) {
                'string' => $rawValue,
                'int' => $this->coerceInt(
                    rawValue: $rawValue,
                    parameterName: $parameterName,
                ),
                'float' => $this->coerceFloat(
                    rawValue: $rawValue,
                    parameterName: $parameterName,
                ),
                'bool' => $this->coerceBool(
                    rawValue: $rawValue,
                    parameterName: $parameterName,
                ),
                default => throw ConsoleException::fromUnsupportedCommandParameterType(
                    parameterName: $parameterName,
                    typeName: $typeName,
                ),
            };
        }

        if (!\enum_exists($typeName)) {
            throw ConsoleException::fromUnsupportedCommandParameterType(
                parameterName: $parameterName,
                typeName: $typeName,
            );
        }

        /** @var class-string<\UnitEnum> $typeName */
        return $this->coerceEnum(
            rawValue: $rawValue,
            typeName: $typeName,
            parameterName: $parameterName,
        );
    }

    private function coerceInt(
        string $rawValue,
        string $parameterName,
    ): int {
        if (\preg_match('/^-?\d+$/', $rawValue) !== 1) {
            throw ConsoleException::fromInvalidCommandArgumentValue(
                parameterName: $parameterName,
                typeName: 'int',
                rawValue: $rawValue,
            );
        }

        return (int) $rawValue;
    }

    private function coerceFloat(
        string $rawValue,
        string $parameterName,
    ): float {
        if (!\is_numeric($rawValue)) {
            throw ConsoleException::fromInvalidCommandArgumentValue(
                parameterName: $parameterName,
                typeName: 'float',
                rawValue: $rawValue,
            );
        }

        return (float) $rawValue;
    }

    private function coerceBool(
        string $rawValue,
        string $parameterName,
    ): bool {
        return match (\strtolower($rawValue)) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw ConsoleException::fromInvalidCommandArgumentValue(
                parameterName: $parameterName,
                typeName: 'bool',
                rawValue: $rawValue,
            ),
        };
    }

    /**
     * @param class-string<\UnitEnum> $typeName
     */
    private function coerceEnum(
        string $rawValue,
        string $typeName,
        string $parameterName,
    ): \UnitEnum {
        if (\is_subclass_of($typeName, \BackedEnum::class)) {
            /** @var class-string<\BackedEnum> $typeName */
            $reflection = new \ReflectionEnum($typeName);
            $backingType = $reflection->getBackingType();

            if ($backingType instanceof \ReflectionNamedType && $backingType->getName() === 'int') {
                if (\preg_match('/^-?\d+$/', $rawValue) !== 1) {
                    throw ConsoleException::fromInvalidCommandArgumentValue(
                        parameterName: $parameterName,
                        typeName: $typeName,
                        rawValue: $rawValue,
                    );
                }

                try {
                    return $typeName::from((int) $rawValue);
                } catch (\ValueError) {
                    throw ConsoleException::fromInvalidCommandArgumentValue(
                        parameterName: $parameterName,
                        typeName: $typeName,
                        rawValue: $rawValue,
                    );
                }
            }

            try {
                return $typeName::from($rawValue);
            } catch (\ValueError) {
                throw ConsoleException::fromInvalidCommandArgumentValue(
                    parameterName: $parameterName,
                    typeName: $typeName,
                    rawValue: $rawValue,
                );
            }
        }

        foreach ($typeName::cases() as $case) {
            if ($case->name === $rawValue) {
                return $case;
            }
        }

        throw ConsoleException::fromInvalidCommandArgumentValue(
            parameterName: $parameterName,
            typeName: $typeName,
            rawValue: $rawValue,
        );
    }
}
