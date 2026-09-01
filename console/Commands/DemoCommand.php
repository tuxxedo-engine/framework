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
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\Renderable\ProgressBar;
use Tuxxedo\Console\Output\Renderable\Spinner;
use Tuxxedo\Console\Output\Renderable\Table;

class DemoCommand
{
    #[Command('demo:table')]
    public function table(
        OutputInterface $output,
    ): ExitCode {
        $output->render(
            new Table(
                headers: [
                    'ID',
                    'Name',
                    'Role',
                    'Status',
                ],
                rows: [
                    [
                        '1',
                        'Alice',
                        'admin',
                        'active',
                    ],
                    [
                        '2',
                        'Bob',
                        'member',
                        'active',
                    ],
                    [
                        '3',
                        'Charlie',
                        'guest',
                        'pending',
                    ],
                    [
                        '4',
                        'Dana',
                        'member',
                        'suspended',
                    ],
                ],
            ),
        );

        return ExitCode::SUCCESS;
    }

    #[Command('demo:progress')]
    public function progress(
        OutputInterface $output,
    ): ExitCode {
        $items = \range(1, 20);
        $bar = new ProgressBar(
            output: $output,
            total: \sizeof($items),
        );

        foreach ($bar->iterate($items) as $item) {
            $bar->setMessage(
                \sprintf(
                    'processing item %d',
                    $item,
                ),
            );

            \usleep(100_000);
        }

        $output->line('all done');

        return ExitCode::SUCCESS;
    }

    #[Command('demo:spinner')]
    public function spinner(
        OutputInterface $output,
    ): ExitCode {
        $spinner = new Spinner(
            output: $output,
            message: 'Working...',
        );

        for ($i = 0; $i < 40; $i++) {
            $spinner->tick();

            \usleep(50_000);
        }

        $spinner->finish();
        $output->line('done');

        return ExitCode::SUCCESS;
    }
}
