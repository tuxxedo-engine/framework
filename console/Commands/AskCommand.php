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
use Tuxxedo\Console\Input\ChoiceQuestion;
use Tuxxedo\Console\Input\ConfirmQuestion;
use Tuxxedo\Console\Input\InputInterface;
use Tuxxedo\Console\Input\Questionnaire;
use Tuxxedo\Console\Input\TextQuestion;
use Tuxxedo\Console\Output\OutputInterface;

class AskCommand
{
    #[Command('demo:ask')]
    public function ask(
        InputInterface $input,
        OutputInterface $output,
    ): ExitCode {
        $name = $input->prompt(
            question: 'What is your name?',
            default: 'stranger',
        );

        $enthusiastic = $input->confirm(
            question: 'Are you excited?',
            default: true,
        );

        $language = $input->choose(
            question: 'Pick a language',
            choices: [
                'PHP',
                'Rust',
                'Go',
                'TypeScript',
            ],
        );

        $output->line(
            \sprintf(
                'Hello, %s. Excited: %s. Favourite: %s.',
                $name,
                $enthusiastic ? 'yes' : 'no',
                $language,
            ),
        );

        return ExitCode::SUCCESS;
    }

    #[Command('demo:ask:form')]
    public function form(
        InputInterface $input,
        OutputInterface $output,
    ): ExitCode {
        $answers = $input->ask(
            new Questionnaire(
                questions: [
                    new TextQuestion(
                        key: 'project',
                        prompt: 'Project name',
                        default: 'my-app',
                    ),
                    new ChoiceQuestion(
                        key: 'template',
                        prompt: 'Template',
                        choices: [
                            'blank',
                            'minimal',
                            'full-stack',
                        ],
                    ),
                    new ConfirmQuestion(
                        key: 'git',
                        prompt: 'Initialise a git repository?',
                        default: true,
                    ),
                ],
            ),
        );

        $output->line('Answers:');

        foreach ($answers as $key => $value) {
            $output->line(
                \sprintf(
                    '  %s = %s',
                    $key,
                    self::formatAnswer($value),
                ),
            );
        }

        return ExitCode::SUCCESS;
    }

    private static function formatAnswer(
        mixed $value,
    ): string {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        return \var_export($value, true);
    }
}
