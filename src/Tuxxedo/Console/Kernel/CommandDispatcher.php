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

use Tuxxedo\Console\Config\SuggestionPolicy;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Invocation\ArgvParserInterface;
use Tuxxedo\Console\Invocation\ParameterBinderInterface;
use Tuxxedo\Console\Middleware\CommandInvocation;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Container\ContainerException;
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
        $match = $this->findDescriptor($argv);

        if ($match === null) {
            if ($argv === []) {
                throw ConsoleException::fromNoCommandGiven();
            }

            $suggestion = $this->closestPath($argv);

            throw $suggestion === null
                ? ConsoleException::fromUnrecognizedCommand($argv)
                : ConsoleException::fromUnrecognizedCommandWithSuggestion($argv, $suggestion);
        }

        return $this->buildInvocation(
            descriptor: $match['descriptor'],
            argvTail: $match['tail'],
        );
    }

    /**
     * @param list<string> $argv
     */
    private function closestPath(
        array $argv,
    ): ?string {
        $policy = $this->suggestionPolicy();

        if (!$policy->enabled) {
            return null;
        }

        $input = \join(' ', $argv);
        $best = null;
        $bestDistance = $policy->maxDistance + 1;

        foreach ($this->registry->commands as $descriptor) {
            $candidate = \join(' ', $descriptor->path);
            $distance = \levenshtein($input, $candidate);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $bestDistance <= $policy->maxDistance
            ? $best
            : null;
    }

    private function suggestionPolicy(): SuggestionPolicy
    {
        try {
            return $this->container->resolve(SuggestionPolicy::class);
        } catch (ContainerException) {
            return SuggestionPolicy::default();
        }
    }

    public function findDescriptor(
        array $argv,
    ): ?array {
        if ($argv === []) {
            $default = $this->registry->defaultCommand;

            if ($default === null) {
                return null;
            }

            return [
                'descriptor' => $default,
                'tail' => [],
            ];
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

            return [
                'descriptor' => $descriptor,
                'tail' => $tail,
            ];
        }

        return null;
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
