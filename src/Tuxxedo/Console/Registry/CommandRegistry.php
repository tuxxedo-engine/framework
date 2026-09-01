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

namespace Tuxxedo\Console\Registry;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;

class CommandRegistry implements CommandRegistryInterface
{
    /**
     * @var list<CommandDescriptorInterface>
     */
    public readonly array $commands;

    public readonly ?CommandDescriptorInterface $defaultCommand;

    /**
     * @param list<CommandDescriptorInterface> $commands
     *
     * @throws ConsoleException
     */
    public function __construct(
        array $commands,
    ) {
        $seen = [];
        $named = [];
        $default = null;

        foreach ($commands as $command) {
            if ($command->path === []) {
                if ($default !== null) {
                    throw ConsoleException::fromMultipleDefaultCommands();
                }

                $default = $command;

                continue;
            }

            $key = \join(' ', $command->path);

            if (isset($seen[$key])) {
                throw ConsoleException::fromDuplicateCommandPath($command->path);
            }

            $seen[$key] = true;
            $named[] = $command;
        }

        $this->commands = $named;
        $this->defaultCommand = $default;
    }

    /**
     * @param list<string> $path
     */
    public function find(
        array $path,
    ): ?CommandDescriptorInterface {
        foreach ($this->commands as $command) {
            if ($command->path === $path) {
                return $command;
            }
        }

        return null;
    }
}
