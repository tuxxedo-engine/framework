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
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\FrameSequence;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\ProgressBar;
use Tuxxedo\Console\Output\ProgressBarTheme;
use Tuxxedo\Console\Output\Spinner;
use Tuxxedo\Console\Output\SpinnerTheme;
use Tuxxedo\Console\Output\Style\Decoration;
use Tuxxedo\Console\Output\Style\Style;
use Tuxxedo\Console\Output\StyledBlock;
use Tuxxedo\Console\Output\StyledCell;
use Tuxxedo\Console\Output\Table;

class ShowcaseCommand
{
    #[Command('demo:showcase')]
    public function showcase(
        OutputInterface $output,
    ): ExitCode {
        $this->sectionSugar($output);
        $this->sectionStyledBlock($output);
        $this->sectionTable($output);
        $this->sectionProgressBar($output);
        $this->sectionSpinner($output);
        $this->sectionRandomPalette($output);

        return ExitCode::SUCCESS;
    }

    private function sectionSugar(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'Output sugar');

        $output->error('error: something broke');
        $output->line();
        $output->success('success: everything worked');
        $output->line();
        $output->warning('warning: heads up');
        $output->line();
        $output->info('info: fyi');
        $output->line();
        $output->styled(
            bytes: 'custom: bold + underlined magenta',
            style: new Style(
                foreground: Color::LIGHT_MAGENTA,
                decorations: [
                    Decoration::BOLD,
                    Decoration::UNDERLINE,
                ],
            ),
        );
        $output->line();
        $output->line();
    }

    private function sectionStyledBlock(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'StyledBlock');

        (new StyledBlock(
            lines: [
                'Deploy complete!',
                '12 files migrated in 4.2s',
            ],
            style: Style::success(),
            padding: 2,
        ))->render(output: $output);
        $output->line();
    }

    private function sectionTable(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'Table with headerStyle + borderStyle + StyledCell');

        (new Table(
            headers: [
                'User',
                'Role',
                'Status',
            ],
            rows: [
                [
                    'alice',
                    'admin',
                    new StyledCell(value: 'active', style: Style::success()),
                ],
                [
                    'bob',
                    'guest',
                    new StyledCell(value: 'pending', style: Style::warning()),
                ],
                [
                    'carol',
                    'admin',
                    new StyledCell(value: 'blocked', style: Style::error()),
                ],
            ],
            headerStyle: new Style(
                foreground: Color::LIGHT_CYAN,
                decorations: [
                    Decoration::BOLD,
                ],
            ),
            borderStyle: Style::dim(),
        ))->render(output: $output);
        $output->line();
    }

    private function sectionProgressBar(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'ProgressBar with styled parts');

        $bar = new ProgressBar(
            output: $output,
            total: 40,
            theme: new ProgressBarTheme(
                width: 30,
                filledSegment: '█',
                emptySegment: '░',
                head: '',
                leadingCap: '[',
                trailingCap: ']',
                filledStyle: Style::success(),
                emptyStyle: Style::dim(),
                capStyle: new Style(foreground: Color::LIGHT_BLUE),
                percentageStyle: new Style(
                    foreground: Color::LIGHT_YELLOW,
                    decorations: [
                        Decoration::BOLD,
                    ],
                ),
            ),
        );

        for ($i = 0; $i < 40; $i++) {
            \usleep(30_000);

            $bar->advance();
        }

        $bar->finish();
        $output->line();
    }

    private function sectionSpinner(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'Spinner with frame + message styling');

        $spinner = new Spinner(
            output: $output,
            theme: new SpinnerTheme(
                frames: FrameSequence::brailleDots(),
                frameStyle: new Style(foreground: Color::LIGHT_MAGENTA),
                messageStyle: Style::info(),
            ),
            message: 'crunching numbers...',
        );

        for ($i = 0; $i < 40; $i++) {
            \usleep(50_000);

            $spinner->tick();
        }

        $spinner->finish();
        $output->line();
    }

    private function sectionRandomPalette(
        OutputInterface $output,
    ): void {
        $this->header(output: $output, title: 'Random-palette lines (caller-composed)');

        $palette = [
            Color::LIGHT_CYAN,
            Color::LIGHT_MAGENTA,
            Color::LIGHT_YELLOW,
            Color::LIGHT_GREEN,
            Color::LIGHT_BLUE,
            Color::LIGHT_RED,
        ];

        $lines = [
            'framework provides Style + Color primitives',
            'caller composes randomness however they like',
            'no framework-side randomizer, no coupling',
            'perfect for supervisor logs, per-worker tint',
            'or a rainbow banner if you feel fancy',
        ];

        foreach ($lines as $line) {
            $output->styled(
                bytes: $line,
                style: new Style(foreground: $palette[\array_rand($palette)]),
            );
            $output->line();
        }

        $output->line();
    }

    private function header(
        OutputInterface $output,
        string $title,
    ): void {
        $output->styled(
            bytes: '── ' . $title . ' ──',
            style: new Style(
                foreground: Color::LIGHT_WHITE,
                decorations: [
                    Decoration::BOLD,
                ],
            ),
        );
        $output->line();
        $output->line();
    }
}
