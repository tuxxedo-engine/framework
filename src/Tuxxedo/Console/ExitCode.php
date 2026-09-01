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

namespace Tuxxedo\Console;

enum ExitCode: int
{
    case SUCCESS = 0;
    case FAILURE = 1;
    case MISUSE = 2;
    case USAGE = 64;
    case DATA_ERROR = 65;
    case NO_INPUT = 66;
    case NO_USER = 67;
    case NO_HOST = 68;
    case UNAVAILABLE = 69;
    case SOFTWARE_ERROR = 70;
    case OS_ERROR = 71;
    case OS_FILE_ERROR = 72;
    case CANT_CREATE = 73;
    case IO_ERROR = 74;
    case TEMPORARY_FAILURE = 75;
    case PROTOCOL_ERROR = 76;
    case NO_PERMISSION = 77;
    case CONFIG_ERROR = 78;
    case CANNOT_EXECUTE = 126;
    case COMMAND_NOT_FOUND = 127;
    case INVALID_EXIT_ARGUMENT = 128;
    case HANGUP = 129;
    case INTERRUPTED = 130;
    case QUIT_SIGNAL = 131;
    case ILLEGAL_INSTRUCTION = 132;
    case TRACE_TRAP = 133;
    case ABORTED = 134;
    case BUS_ERROR = 135;
    case FLOATING_POINT_ERROR = 136;
    case KILLED = 137;
    case USER_SIGNAL_1 = 138;
    case SEGMENTATION_FAULT = 139;
    case USER_SIGNAL_2 = 140;
    case BROKEN_PIPE = 141;
    case ALARM = 142;
    case TERMINATED = 143;
    case CHILD_STATUS = 145;
    case CONTINUED = 146;
    case STOPPED = 147;
    case KEYBOARD_STOP = 148;
    case TTY_INPUT = 149;
    case TTY_OUTPUT = 150;
    case OUT_OF_RANGE = 255;

    public function description(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::FAILURE => 'General failure',
            self::MISUSE => 'Misuse of shell builtin',
            self::USAGE => 'Usage error',
            self::DATA_ERROR => 'Data format error',
            self::NO_INPUT => 'Cannot open input',
            self::NO_USER => 'Addressee unknown',
            self::NO_HOST => 'Host name unknown',
            self::UNAVAILABLE => 'Service unavailable',
            self::SOFTWARE_ERROR => 'Internal software error',
            self::OS_ERROR => 'System error',
            self::OS_FILE_ERROR => 'Critical OS file missing',
            self::CANT_CREATE => 'Cannot create output file',
            self::IO_ERROR => 'I/O error',
            self::TEMPORARY_FAILURE => 'Temporary failure',
            self::PROTOCOL_ERROR => 'Remote protocol error',
            self::NO_PERMISSION => 'Permission denied',
            self::CONFIG_ERROR => 'Configuration error',
            self::CANNOT_EXECUTE => 'Command cannot execute',
            self::COMMAND_NOT_FOUND => 'Command not found',
            self::INVALID_EXIT_ARGUMENT => 'Invalid argument to exit',
            self::HANGUP => 'Hangup',
            self::INTERRUPTED => 'Interrupted',
            self::QUIT_SIGNAL => 'Quit',
            self::ILLEGAL_INSTRUCTION => 'Illegal instruction',
            self::TRACE_TRAP => 'Trace trap',
            self::ABORTED => 'Aborted',
            self::BUS_ERROR => 'Bus error',
            self::FLOATING_POINT_ERROR => 'Floating point error',
            self::KILLED => 'Killed',
            self::USER_SIGNAL_1 => 'User signal 1',
            self::SEGMENTATION_FAULT => 'Segmentation fault',
            self::USER_SIGNAL_2 => 'User signal 2',
            self::BROKEN_PIPE => 'Broken pipe',
            self::ALARM => 'Alarm',
            self::TERMINATED => 'Terminated',
            self::CHILD_STATUS => 'Child status changed',
            self::CONTINUED => 'Continued',
            self::STOPPED => 'Stopped',
            self::KEYBOARD_STOP => 'Keyboard stop',
            self::TTY_INPUT => 'TTY input',
            self::TTY_OUTPUT => 'TTY output',
            self::OUT_OF_RANGE => 'Exit status out of range',
        };
    }
}
