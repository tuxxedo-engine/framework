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

namespace Unit\Console\Input;

use Fixture\Console\Input\ChoiceBackedEnum;
use Fixture\Console\Input\ChoiceUnitEnum;
use PHPUnit\Framework\TestCase;
use Support\Console\Stream\BufferedOutputStream;
use Support\Console\Stream\ScriptedInputStream;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Input\ChoiceQuestion;
use Tuxxedo\Console\Input\ConfirmQuestion;
use Tuxxedo\Console\Input\Questionnaire;
use Tuxxedo\Console\Input\StdinInput;
use Tuxxedo\Console\Input\TextQuestion;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\StreamOutput;

class StdinInputTest extends TestCase
{
    private BufferedOutputStream $outputStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputStream = new BufferedOutputStream();
    }

    public function testReadLineDelegatesToStream(): void
    {
        $input = $this->makeInput(
            lines: [
                'hello',
            ],
        );

        self::assertSame(
            'hello',
            $input->readLine(),
        );

        self::assertNull($input->readLine());
    }

    public function testReadAllDrainsStream(): void
    {
        $input = $this->makeInput(
            lines: [
                'one',
                'two',
            ],
        );

        self::assertSame(
            'one' . "\n" . 'two',
            $input->readAll(),
        );
    }

    public function testIsInteractiveReflectsStreamTerminal(): void
    {
        $interactive = new StdinInput(
            stream: new ScriptedInputStream(
                isTerminal: true,
            ),
            output: $this->makeOutput(),
        );

        $piped = new StdinInput(
            stream: new ScriptedInputStream(),
            output: $this->makeOutput(),
        );

        self::assertTrue($interactive->isInteractive);
        self::assertFalse($piped->isInteractive);
    }

    public function testPromptReturnsTrimmedAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                '  answer  ',
            ],
        );

        self::assertSame(
            'answer',
            $input->prompt(
                question: 'Name?',
            ),
        );

        self::assertStringContainsString(
            'Name?',
            $this->outputStream->bytes,
        );
    }

    public function testPromptWritesDefaultSuffixWhenDefaultProvided(): void
    {
        $input = $this->makeInput(
            lines: [
                'x',
            ],
        );

        $input->prompt(
            question: 'Name?',
            default: 'alice',
        );

        self::assertStringContainsString(
            '[default: alice]',
            $this->outputStream->bytes,
        );
    }

    public function testPromptReturnsDefaultOnEmptyAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                '',
            ],
        );

        self::assertSame(
            'fallback',
            $input->prompt(
                question: 'Name?',
                default: 'fallback',
            ),
        );
    }

    public function testPromptReturnsDefaultOnEof(): void
    {
        $input = $this->makeInput(
            lines: [],
        );

        self::assertSame(
            'fallback',
            $input->prompt(
                question: 'Name?',
                default: 'fallback',
            ),
        );
    }

    public function testPromptThrowsOnEmptyAnswerWithoutDefault(): void
    {
        $input = $this->makeInput(
            lines: [
                '',
            ],
        );

        $this->expectException(ConsoleException::class);

        $input->prompt(
            question: 'Name?',
        );
    }

    public function testPromptThrowsOnEofWithoutDefault(): void
    {
        $input = $this->makeInput(
            lines: [],
        );

        $this->expectException(ConsoleException::class);

        $input->prompt(
            question: 'Name?',
        );
    }

    public function testConfirmReturnsTrueForYes(): void
    {
        $input = $this->makeInput(
            lines: [
                'y',
            ],
        );

        self::assertTrue(
            $input->confirm(
                question: 'Sure?',
            ),
        );
    }

    public function testConfirmReturnsFalseForNo(): void
    {
        $input = $this->makeInput(
            lines: [
                'no',
            ],
        );

        self::assertFalse(
            $input->confirm(
                question: 'Sure?',
            ),
        );
    }

    public function testConfirmReturnsDefaultOnEmptyAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                '',
            ],
        );

        self::assertTrue(
            $input->confirm(
                question: 'Sure?',
                default: true,
            ),
        );
    }

    public function testConfirmReturnsDefaultOnEof(): void
    {
        $input = $this->makeInput(
            lines: [],
        );

        self::assertFalse(
            $input->confirm(
                question: 'Sure?',
            ),
        );
    }

    public function testConfirmWritesIndicatorReflectingDefault(): void
    {
        $input = $this->makeInput(
            lines: [
                'y',
            ],
        );

        $input->confirm(
            question: 'Sure?',
            default: true,
        );

        self::assertStringContainsString(
            '[Y/n]',
            $this->outputStream->bytes,
        );
    }

    public function testConfirmReprompsUntilValidAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                'maybe',
                'sometimes',
                'yes',
            ],
        );

        self::assertTrue(
            $input->confirm(
                question: 'Sure?',
            ),
        );

        self::assertStringContainsString(
            'Invalid choice',
            $this->outputStream->bytes,
        );
    }

    public function testChooseReturnsSelectedChoice(): void
    {
        $input = $this->makeInput(
            lines: [
                '2',
            ],
        );

        self::assertSame(
            'blue',
            $input->choose(
                question: 'Color?',
                choices: [
                    'red',
                    'blue',
                    'green',
                ],
            ),
        );
    }

    public function testChooseRendersNumberedList(): void
    {
        $input = $this->makeInput(
            lines: [
                '1',
            ],
        );

        $input->choose(
            question: 'Pick',
            choices: [
                'first',
                'second',
            ],
        );

        self::assertStringContainsString(
            '[1] first',
            $this->outputStream->bytes,
        );

        self::assertStringContainsString(
            '[2] second',
            $this->outputStream->bytes,
        );
    }

    public function testChooseReprompsOnNonNumericAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                'nope',
                '1',
            ],
        );

        self::assertSame(
            'a',
            $input->choose(
                question: 'Pick',
                choices: [
                    'a',
                ],
            ),
        );
        self::assertStringContainsString(
            'Invalid choice',
            $this->outputStream->bytes,
        );
    }

    public function testChooseReprompsOnOutOfRangeSelection(): void
    {
        $input = $this->makeInput(
            lines: [
                '99',
                '1',
            ],
        );

        self::assertSame(
            'only',
            $input->choose(
                question: 'Pick',
                choices: [
                    'only',
                ],
            ),
        );
    }

    public function testChooseReprompsOnEmptyAnswer(): void
    {
        $input = $this->makeInput(
            lines: [
                '',
                '1',
            ],
        );

        self::assertSame(
            'a',
            $input->choose(
                question: 'Pick',
                choices: [
                    'a',
                ],
            ),
        );
    }

    public function testChooseThrowsOnEmptyChoiceList(): void
    {
        $input = $this->makeInput(
            lines: [],
        );

        $this->expectException(ConsoleException::class);

        $input->choose(
            question: 'Pick',
            choices: [],
        );
    }

    public function testChooseThrowsOnEofBeforeSelection(): void
    {
        $input = $this->makeInput(
            lines: [],
        );

        $this->expectException(ConsoleException::class);

        $input->choose(
            question: 'Pick',
            choices: [
                'a',
            ],
        );
    }

    public function testChooseStringifiesBackedEnumChoiceLabel(): void
    {
        $input = $this->makeInput(
            lines: [
                '1',
            ],
        );

        $input->choose(
            question: 'Pick',
            choices: [
                ChoiceBackedEnum::FIRST,
            ],
        );

        self::assertStringContainsString(
            '[1] first-value',
            $this->outputStream->bytes,
        );
    }

    public function testChooseStringifiesUnitEnumChoiceLabel(): void
    {
        $input = $this->makeInput(
            lines: [
                '1',
            ],
        );

        $input->choose(
            question: 'Pick',
            choices: [
                ChoiceUnitEnum::ALPHA,
            ],
        );

        self::assertStringContainsString(
            '[1] ALPHA',
            $this->outputStream->bytes,
        );
    }

    public function testAskRoutesEachQuestionByType(): void
    {
        $input = $this->makeInput(
            lines: [
                'kalle',
                'y',
                '2',
            ],
        );

        $answers = $input->ask(
            new Questionnaire(
                questions: [
                    new TextQuestion(
                        key: 'name',
                        prompt: 'Name?',
                    ),
                    new ConfirmQuestion(
                        key: 'agree',
                        prompt: 'Agree?',
                    ),
                    new ChoiceQuestion(
                        key: 'color',
                        prompt: 'Color?',
                        choices: [
                            'red',
                            'blue',
                        ],
                    ),
                ],
            ),
        );

        self::assertSame(
            [
                'name' => 'kalle',
                'agree' => true,
                'color' => 'blue',
            ],
            $answers,
        );
    }

    /**
     * @param list<string> $lines
     */
    private function makeInput(
        array $lines,
    ): StdinInput {
        return new StdinInput(
            stream: new ScriptedInputStream(
                lines: $lines,
            ),
            output: $this->makeOutput(),
        );
    }

    private function makeOutput(): StreamOutput
    {
        return new StreamOutput(
            stream: $this->outputStream,
            decorationMode: DecorationMode::NEVER,
        );
    }
}
