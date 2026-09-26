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

namespace Console\Commands;

use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Kernel\CommandRegistryInterface;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\Table;

class DebugCommand
{
    public function __construct(
        private readonly CommandRegistryInterface $registry,
    ) {
    }

    #[Command('debug:commands')]
    public function commands(
        OutputInterface $output,
    ): ExitCode {
        $rows = [];

        foreach ($this->registry->commands as $descriptor) {
            $rows[] = [
                \join(' ', $descriptor->path),
                $descriptor->description ?? '',
                $descriptor->hasReturnValue ? 'yes' : 'no',
                (string) \sizeof($descriptor->arguments),
                (string) \sizeof($descriptor->options),
                (string) \sizeof($descriptor->flags),
            ];
        }

        (new Table(
            headers: [
                'Path',
                'Description',
                'Returns',
                'Args',
                'Opts',
                'Flags',
            ],
            rows: $rows,
        ))->render(output: $output);

        return ExitCode::SUCCESS;
    }
}
