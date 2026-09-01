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

namespace Tuxxedo\Console\Discovery;

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\Attribute\CommandParameterInterface;
use Tuxxedo\Console\Attribute\DefaultCommand;
use Tuxxedo\Console\Attribute\Flag;
use Tuxxedo\Console\Attribute\Option;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\ArgumentDescriptor;
use Tuxxedo\Console\Descriptor\ArgumentDescriptorInterface;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\Descriptor\FlagDescriptor;
use Tuxxedo\Console\Descriptor\FlagDescriptorInterface;
use Tuxxedo\Console\Descriptor\OptionDescriptor;
use Tuxxedo\Console\Descriptor\OptionDescriptorInterface;
use Tuxxedo\Console\ExitCode;

class CommandDiscoverer implements CommandDiscovererInterface
{
    /**
     * @param class-string $className
     *
     * @return list<CommandDescriptorInterface>
     *
     * @throws ConsoleException
     */
    public function discover(
        string $className,
    ): array {
        $reflection = new \ReflectionClass($className);
        $descriptors = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $commandAttributes = $method->getAttributes(
                name: Command::class,
                flags: \ReflectionAttribute::IS_INSTANCEOF,
            );
            $defaultAttributes = $method->getAttributes(DefaultCommand::class);

            if ($commandAttributes === [] && $defaultAttributes === []) {
                continue;
            }

            if ($commandAttributes !== [] && $defaultAttributes !== []) {
                throw ConsoleException::fromConflictingCommandKind(
                    className: $className,
                    methodName: $method->getName(),
                );
            }

            $hasReturnValue = $this->extractHasReturnValue(
                className: $className,
                method: $method,
            );

            $binding = $this->extractParameterBindings(
                className: $className,
                method: $method,
            );

            if ($defaultAttributes !== []) {
                $default = $defaultAttributes[0]->newInstance();

                $descriptors[] = new CommandDescriptor(
                    path: [],
                    description: $default->description,
                    hasReturnValue: $hasReturnValue,
                    arguments: $binding['arguments'],
                    options: $binding['options'],
                    flags: $binding['flags'],
                    className: $className,
                    methodName: $method->getName(),
                );

                continue;
            }

            foreach ($commandAttributes as $attribute) {
                $command = $attribute->newInstance();

                $descriptors[] = new CommandDescriptor(
                    path: $command->path,
                    description: $command->description,
                    hasReturnValue: $hasReturnValue,
                    arguments: $binding['arguments'],
                    options: $binding['options'],
                    flags: $binding['flags'],
                    className: $className,
                    methodName: $method->getName(),
                );
            }
        }

        return $descriptors;
    }

    /**
     * @param class-string $className
     */
    private function extractHasReturnValue(
        string $className,
        \ReflectionMethod $method,
    ): bool {
        $returnType = $method->getReturnType();

        if (!$returnType instanceof \ReflectionNamedType) {
            throw ConsoleException::fromInvalidCommandReturnType(
                className: $className,
                methodName: $method->getName(),
            );
        }

        $name = $returnType->getName();

        if ($name === 'void') {
            return false;
        }

        if ($name === ExitCode::class) {
            return true;
        }

        throw ConsoleException::fromInvalidCommandReturnType(
            className: $className,
            methodName: $method->getName(),
        );
    }

    /**
     * @param class-string $className
     *
     * @return array{
     *     arguments: list<ArgumentDescriptorInterface>,
     *     options: list<OptionDescriptorInterface>,
     *     flags: list<FlagDescriptorInterface>,
     * }
     */
    private function extractParameterBindings(
        string $className,
        \ReflectionMethod $method,
    ): array {
        $arguments = [];
        $options = [];
        $flags = [];
        $argumentPosition = 0;

        foreach ($method->getParameters() as $parameter) {
            $bindings = $parameter->getAttributes(
                name: CommandParameterInterface::class,
                flags: \ReflectionAttribute::IS_INSTANCEOF,
            );

            if (\sizeof($bindings) > 1) {
                throw ConsoleException::fromConflictingCommandParameterAttributes(
                    className: $className,
                    methodName: $method->getName(),
                    parameterName: $parameter->getName(),
                );
            }

            if ($bindings === []) {
                continue;
            }

            $binding = $bindings[0]->newInstance();
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                throw ConsoleException::fromMissingCommandParameterType(
                    className: $className,
                    methodName: $method->getName(),
                    parameterName: $parameter->getName(),
                );
            }

            $name = $binding->name ?? $parameter->getName();
            $hasDefault = $parameter->isDefaultValueAvailable();
            $default = $hasDefault
                ? $parameter->getDefaultValue()
                : null;

            if ($binding instanceof Argument) {
                $arguments[] = new ArgumentDescriptor(
                    name: $name,
                    position: $argumentPosition,
                    description: $binding->description,
                    typeName: $type->getName(),
                    isBuiltin: $type->isBuiltin(),
                    isNullable: $type->allowsNull(),
                    hasDefault: $hasDefault,
                    default: $default,
                    isVariadic: $parameter->isVariadic(),
                );

                $argumentPosition++;

                continue;
            }

            if ($binding instanceof Option) {
                $options[] = new OptionDescriptor(
                    name: $name,
                    short: $binding->short,
                    description: $binding->description,
                    typeName: $type->getName(),
                    isBuiltin: $type->isBuiltin(),
                    isNullable: $type->allowsNull(),
                    hasDefault: $hasDefault,
                    default: $default,
                    repeatable: $binding->repeatable,
                );

                continue;
            }

            /** @var Flag $binding */
            $flags[] = new FlagDescriptor(
                name: $name,
                short: $binding->short,
                description: $binding->description,
            );
        }

        return [
            'arguments' => $arguments,
            'options' => $options,
            'flags' => $flags,
        ];
    }
}
