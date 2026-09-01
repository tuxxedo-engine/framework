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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Invocation\ArgvParserInterface;
use Tuxxedo\Console\Invocation\ParameterBinderInterface;
use Tuxxedo\Console\Middleware\CommandInvocation;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Console\Registry\CommandRegistryInterface;
use Tuxxedo\Container\ContainerInterface;

class CommandDispatcher implements CommandDispatcherInterface
{
    public function __construct(
        private readonly CommandRegistryInterface $registry,
        private readonly ArgvParserInterface $parser,
        private readonly ParameterBinderInterface $binder,
        private readonly ContainerInterface $container,
    ) {
    }

    public function resolve(
        array $argv,
    ): CommandInvocationInterface {
        if ($argv === []) {
            $default = $this->registry->defaultCommand;

            if ($default === null) {
                throw ConsoleException::fromNoCommandGiven();
            }

            return $this->buildInvocation(
                descriptor: $default,
                argvTail: [],
            );
        }

        $count = \sizeof($argv);

        for ($i = $count; $i >= 1; $i--) {
            /** @var list<string> $prefix */
            $prefix = \array_slice($argv, 0, $i);
            $descriptor = $this->registry->find($prefix);

            if ($descriptor === null) {
                continue;
            }

            /** @var list<string> $tail */
            $tail = \array_slice($argv, $i);

            return $this->buildInvocation(
                descriptor: $descriptor,
                argvTail: $tail,
            );
        }

        throw ConsoleException::fromUnrecognizedCommand($argv);
    }

    /**
     * @param list<string> $argvTail
     */
    private function buildInvocation(
        CommandDescriptorInterface $descriptor,
        array $argvTail,
    ): CommandInvocationInterface {
        $parsed = $this->parser->parse(
            argv: $argvTail,
            descriptor: $descriptor,
        );
        $arguments = $this->binder->bind(
            argv: $parsed,
            descriptor: $descriptor,
        );

        return new CommandInvocation(
            descriptor: $descriptor,
            arguments: $arguments,
        );
    }

    public function execute(
        CommandInvocationInterface $invocation,
    ): ExitCode {
        $descriptor = $invocation->descriptor;
        $handler = $this->container->resolve($descriptor->className);
        $method = new \ReflectionMethod(
            $descriptor->className,
            $descriptor->methodName,
        );

        if (!$descriptor->hasReturnValue) {
            $method->invokeArgs($handler, $invocation->arguments);

            return ExitCode::SUCCESS;
        }

        /** @var ExitCode $result */
        $result = $method->invokeArgs($handler, $invocation->arguments);

        return $result;
    }
}
