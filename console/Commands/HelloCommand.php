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

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Output\OutputInterface;

class HelloCommand
{
    #[Command('hello')]
    public function greet(
        OutputInterface $output,
        #[Argument]
        string $name = 'world',
    ): ExitCode {
        $output->line(
            \sprintf(
                'Hello, %s',
                $name,
            ),
        );

        return ExitCode::SUCCESS;
    }

    #[Command('error')]
    public function error(): void
    {
        throw new \Exception('Error!');
    }

    #[Command('error2')]
    public function errorNested(): void
    {
        throw new \Exception(
            message: 'Error!',
            previous: new \Exception(
                message: 'Error 1!',
                previous: new \Exception(
                    message: 'Error 2!',
                ),
            ),
        );
    }
}
