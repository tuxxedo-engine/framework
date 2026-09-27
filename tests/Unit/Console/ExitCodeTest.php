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

namespace Unit\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\ExitCode;

class ExitCodeTest extends TestCase
{
    /**
     * @return \Generator<array{0: ExitCode, 1: int, 2: string}>
     */
    public static function exitCodeDataProvider(): \Generator
    {
        yield [
            ExitCode::SUCCESS,
            0,
            'Success',
        ];

        yield [
            ExitCode::FAILURE,
            1,
            'General failure',
        ];

        yield [
            ExitCode::MISUSE,
            2,
            'Misuse of shell builtin',
        ];

        yield [
            ExitCode::USAGE,
            64,
            'Usage error',
        ];

        yield [
            ExitCode::DATA_ERROR,
            65,
            'Data format error',
        ];

        yield [
            ExitCode::NO_INPUT,
            66,
            'Cannot open input',
        ];

        yield [
            ExitCode::NO_USER,
            67,
            'Addressee unknown',
        ];

        yield [
            ExitCode::NO_HOST,
            68,
            'Host name unknown',
        ];

        yield [
            ExitCode::UNAVAILABLE,
            69,
            'Service unavailable',
        ];

        yield [
            ExitCode::SOFTWARE_ERROR,
            70,
            'Internal software error',
        ];

        yield [
            ExitCode::OS_ERROR,
            71,
            'System error',
        ];

        yield [
            ExitCode::OS_FILE_ERROR,
            72,
            'Critical OS file missing',
        ];

        yield [
            ExitCode::CANT_CREATE,
            73,
            'Cannot create output file',
        ];

        yield [
            ExitCode::IO_ERROR,
            74,
            'I/O error',
        ];

        yield [
            ExitCode::TEMPORARY_FAILURE,
            75,
            'Temporary failure',
        ];

        yield [
            ExitCode::PROTOCOL_ERROR,
            76,
            'Remote protocol error',
        ];

        yield [
            ExitCode::NO_PERMISSION,
            77,
            'Permission denied',
        ];

        yield [
            ExitCode::CONFIG_ERROR,
            78,
            'Configuration error',
        ];

        yield [
            ExitCode::CANNOT_EXECUTE,
            126,
            'Command cannot execute',
        ];

        yield [
            ExitCode::COMMAND_NOT_FOUND,
            127,
            'Command not found',
        ];

        yield [
            ExitCode::INVALID_EXIT_ARGUMENT,
            128,
            'Invalid argument to exit',
        ];

        yield [
            ExitCode::HANGUP,
            129,
            'Hangup',
        ];

        yield [
            ExitCode::INTERRUPTED,
            130,
            'Interrupted',
        ];

        yield [
            ExitCode::QUIT_SIGNAL,
            131,
            'Quit',
        ];

        yield [
            ExitCode::ILLEGAL_INSTRUCTION,
            132,
            'Illegal instruction',
        ];

        yield [
            ExitCode::TRACE_TRAP,
            133,
            'Trace trap',
        ];

        yield [
            ExitCode::ABORTED,
            134,
            'Aborted',
        ];

        yield [
            ExitCode::BUS_ERROR,
            135,
            'Bus error',
        ];

        yield [
            ExitCode::FLOATING_POINT_ERROR,
            136,
            'Floating point error',
        ];

        yield [
            ExitCode::KILLED,
            137,
            'Killed',
        ];

        yield [
            ExitCode::USER_SIGNAL_1,
            138,
            'User signal 1',
        ];

        yield [
            ExitCode::SEGMENTATION_FAULT,
            139,
            'Segmentation fault',
        ];

        yield [
            ExitCode::USER_SIGNAL_2,
            140,
            'User signal 2',
        ];

        yield [
            ExitCode::BROKEN_PIPE,
            141,
            'Broken pipe',
        ];

        yield [
            ExitCode::ALARM,
            142,
            'Alarm',
        ];

        yield [
            ExitCode::TERMINATED,
            143,
            'Terminated',
        ];

        yield [
            ExitCode::CHILD_STATUS,
            145,
            'Child status changed',
        ];

        yield [
            ExitCode::CONTINUED,
            146,
            'Continued',
        ];

        yield [
            ExitCode::STOPPED,
            147,
            'Stopped',
        ];

        yield [
            ExitCode::KEYBOARD_STOP,
            148,
            'Keyboard stop',
        ];

        yield [
            ExitCode::TTY_INPUT,
            149,
            'TTY input',
        ];

        yield [
            ExitCode::TTY_OUTPUT,
            150,
            'TTY output',
        ];

        yield [
            ExitCode::OUT_OF_RANGE,
            255,
            'Exit status out of range',
        ];
    }

    #[DataProvider('exitCodeDataProvider')]
    public function testExitCodeValueAndDescription(
        ExitCode $code,
        int $expectedValue,
        string $expectedDescription,
    ): void {
        self::assertSame($expectedValue, $code->value);
        self::assertSame($expectedDescription, $code->description());
    }
}
