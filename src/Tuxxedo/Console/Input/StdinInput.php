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

namespace Tuxxedo\Console\Input;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Input\Message\DefaultEnglishMessageFormatter;
use Tuxxedo\Console\Input\Message\MessageFormatterInterface;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Stream\InputStreamInterface;

class StdinInput implements InputInterface
{
    public bool $isInteractive {
        get {
            return $this->stream->isTerminal;
        }
    }

    private readonly MessageFormatterInterface $messageFormatter;

    public function __construct(
        public readonly InputStreamInterface $stream,
        private readonly OutputInterface $output,
        ?MessageFormatterInterface $messageFormatter = null,
    ) {
        $this->messageFormatter = $messageFormatter ?? new DefaultEnglishMessageFormatter();
    }

    public function readLine(): ?string
    {
        return $this->stream->readLine();
    }

    public function readAll(): string
    {
        return $this->stream->readAll();
    }

    public function prompt(
        string $question,
        ?string $default = null,
    ): string {
        $suffix = $default !== null
            ? $this->messageFormatter->forDefaultSuffix(default: $default)
            : '';

        $this->output->write($question . $suffix . ' ');

        $answer = $this->stream->readLine();

        if ($answer === null) {
            if ($default !== null) {
                return $default;
            }

            throw ConsoleException::fromEofOnRequiredInput(
                formatter: $this->messageFormatter,
            );
        }

        $trimmed = \trim($answer);

        if ($trimmed === '') {
            if ($default !== null) {
                return $default;
            }

            throw ConsoleException::fromEmptyAnswerNotAllowed(
                formatter: $this->messageFormatter,
            );
        }

        return $trimmed;
    }

    public function confirm(
        string $question,
        bool $default = false,
    ): bool {
        $indicator = $this->messageFormatter->forConfirmIndicator(default: $default);

        while (true) {
            $this->output->write($question . ' ' . $indicator . ' ');

            $answer = $this->stream->readLine();

            if ($answer === null) {
                return $default;
            }

            $normalized = \strtolower(\trim($answer));

            if ($normalized === '') {
                return $default;
            }

            $parsed = $this->messageFormatter->parseConfirmAnswer(normalized: $normalized);

            if ($parsed !== null) {
                return $parsed;
            }

            $this->output->line($this->messageFormatter->forInvalidChoice());
        }
    }

    /**
     * @template TChoice
     *
     * @param list<TChoice> $choices
     *
     * @return TChoice
     */
    public function choose(
        string $question,
        array $choices,
    ): mixed {
        if ($choices === []) {
            throw ConsoleException::fromEmptyChoiceList();
        }

        while (true) {
            $this->output->line($question);

            foreach ($choices as $index => $choice) {
                $this->output->line(
                    \sprintf('  [%d] %s', $index + 1, $this->stringifyChoice(choice: $choice)),
                );
            }

            $this->output->write('> ');

            $answer = $this->stream->readLine();

            if ($answer === null) {
                throw ConsoleException::fromEofOnRequiredInput(
                    formatter: $this->messageFormatter,
                );
            }

            $trimmed = \trim($answer);

            if ($trimmed === '' || \preg_match(pattern: '/^[1-9]\d*$/', subject: $trimmed) !== 1) {
                $this->output->line($this->messageFormatter->forInvalidChoice());

                continue;
            }

            $selection = (int) $trimmed - 1;

            if (!\array_key_exists($selection, $choices)) {
                $this->output->line($this->messageFormatter->forInvalidChoice());

                continue;
            }

            return $choices[$selection];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function ask(
        Questionnaire $questionnaire,
    ): array {
        $answers = [];

        foreach ($questionnaire->questions as $question) {
            $answers[$question->key] = $this->askOne(question: $question);
        }

        return $answers;
    }

    private function askOne(
        QuestionInterface $question,
    ): mixed {
        if ($question instanceof TextQuestion) {
            return $this->prompt(
                question: $question->prompt,
                default: $question->default,
            );
        }

        if ($question instanceof ConfirmQuestion) {
            return $this->confirm(
                question: $question->prompt,
                default: $question->default,
            );
        }

        if ($question instanceof ChoiceQuestion) {
            return $this->choose(
                question: $question->prompt,
                choices: $question->choices,
            );
        }

        throw ConsoleException::fromUnknownQuestionType(
            className: $question::class,
        );
    }

    private function stringifyChoice(
        mixed $choice,
    ): string {
        if (\is_string($choice)) {
            return $choice;
        }

        if ($choice instanceof \BackedEnum) {
            return (string) $choice->value;
        }

        if ($choice instanceof \UnitEnum) {
            return $choice->name;
        }

        if (\is_scalar($choice)) {
            return (string) $choice;
        }

        if ($choice instanceof \Stringable) {
            return (string) $choice;
        }

        return \get_debug_type($choice);
    }
}
