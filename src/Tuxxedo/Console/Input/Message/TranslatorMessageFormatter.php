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

namespace Tuxxedo\Console\Input\Message;

use Tuxxedo\Intl\TranslatorInterface;

class TranslatorMessageFormatter implements MessageFormatterInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function forConfirmIndicator(
        bool $default,
    ): string {
        $answers = $this->confirmAnswers();
        $yes = $answers['yes'][0] ?? '';
        $no = $answers['no'][0] ?? '';

        return $default
            ? '[' . \mb_strtoupper($yes) . '/' . $no . ']'
            : '[' . $yes . '/' . \mb_strtoupper($no) . ']';
    }

    public function confirmAnswers(): array
    {
        $yes = $this->acceptedAnswers(
            key: 'console.input.confirm.yesAnswers',
        );

        $no = $this->acceptedAnswers(
            key: 'console.input.confirm.noAnswers',
        );

        $ambiguous = \array_intersect($yes, $no);

        return [
            'yes' => \array_values(\array_diff($yes, $ambiguous)),
            'no' => \array_values(\array_diff($no, $ambiguous)),
        ];
    }

    public function parseConfirmAnswer(
        string $answer,
    ): ?bool {
        $normalized = self::normalize(
            answer: $answer,
        );

        $answers = $this->confirmAnswers();

        if (\in_array($normalized, $answers['yes'], true)) {
            return true;
        }

        if (\in_array($normalized, $answers['no'], true)) {
            return false;
        }

        return null;
    }

    public function forDefaultSuffix(
        string $default,
    ): string {
        return $this->translator->translate(
            key: 'console.input.defaultSuffix',
            replacements: [
                'default' => $default,
            ],
        );
    }

    public function forInvalidChoice(): string
    {
        return $this->translator->translate(
            key: 'console.input.invalidChoice',
        );
    }

    public function forEmptyAnswerNotAllowed(): string
    {
        return $this->translator->translate(
            key: 'console.input.emptyAnswerNotAllowed',
        );
    }

    public function forEofOnRequiredInput(): string
    {
        return $this->translator->translate(
            key: 'console.input.eofOnRequiredInput',
        );
    }

    /**
     * @return list<string>
     */
    private function acceptedAnswers(
        string $key,
    ): array {
        if (!$this->translator->has(key: $key)) {
            return [];
        }

        $answers = [];

        foreach (\explode(',', $this->translator->translate(key: $key)) as $answer) {
            $normalized = self::normalize(
                answer: $answer,
            );

            if ($normalized !== '' && !\in_array($normalized, $answers, true)) {
                $answers[] = $normalized;
            }
        }

        return $answers;
    }

    private static function normalize(
        string $answer,
    ): string {
        return \mb_strtolower(\mb_trim($answer));
    }
}
