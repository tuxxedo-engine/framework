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

use Tuxxedo\Console\Output\ConsoleOutputInterface;

class ConsoleException extends \Exception implements ExitCodeInterface, ExitCodeExceptionInterface
{
    public readonly ExitCode $exitCode;

    public function __construct(
        ExitCode $exitCode = ExitCode::FAILURE,
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        $this->exitCode = $exitCode;

        parent::__construct(
            message: $message ?? $exitCode->description(),
            previous: $previous,
        );
    }

    public function handleAsError(
        ConsoleOutputInterface $output,
    ): ExitCode {
        $output->stderr->line($this->getMessage());

        return $this->exitCode;
    }

    public static function fromStreamNotOpen(): self
    {
        return new self(
            exitCode: ExitCode::IO_ERROR,
            message: 'Console stream is not open',
        );
    }

    public static function fromStreamReadFailure(
        ?\Throwable $previous = null,
    ): self {
        return new self(
            exitCode: ExitCode::IO_ERROR,
            message: 'Failed to read from console stream',
            previous: $previous,
        );
    }

    public static function fromStreamWriteFailure(
        ?\Throwable $previous = null,
    ): self {
        return new self(
            exitCode: ExitCode::IO_ERROR,
            message: 'Failed to write to console stream',
            previous: $previous,
        );
    }

    public static function fromEmptyCommandName(): self
    {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: 'Command name must not be empty',
        );
    }

    public static function fromCommandNameParseFailure(): self
    {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: 'Failed to parse command name',
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromInvalidCommandReturnType(
        string $className,
        string $methodName,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command %s::%s must return void or %s',
                $className,
                $methodName,
                ExitCode::class,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromMissingCommandParameterType(
        string $className,
        string $methodName,
        string $parameterName,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command %s::%s parameter $%s must declare a type',
                $className,
                $methodName,
                $parameterName,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromConflictingCommandParameterAttributes(
        string $className,
        string $methodName,
        string $parameterName,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command %s::%s parameter $%s carries more than one binding attribute',
                $className,
                $methodName,
                $parameterName,
            ),
        );
    }

    /**
     * @param list<string> $path
     */
    public static function fromDuplicateCommandPath(
        array $path,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command "%s" is defined more than once',
                \join(' ', $path),
            ),
        );
    }

    public static function fromUnknownOption(
        string $name,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Unknown option "%s"',
                $name,
            ),
        );
    }

    public static function fromMissingOptionValue(
        string $name,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Option "%s" requires a value',
                $name,
            ),
        );
    }

    public static function fromDuplicateOption(
        string $name,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Option "%s" was provided more than once',
                $name,
            ),
        );
    }

    public static function fromFlagWithValue(
        string $name,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Flag "%s" does not take a value',
                $name,
            ),
        );
    }

    public static function fromMissingCommandArgument(
        string $argumentName,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Missing required argument "%s"',
                $argumentName,
            ),
        );
    }

    public static function fromMissingRequiredOption(
        string $optionName,
    ): self {
        return new self(
            exitCode: ExitCode::USAGE,
            message: \sprintf(
                'Missing required option "%s"',
                $optionName,
            ),
        );
    }

    public static function fromInvalidCommandArgumentValue(
        string $parameterName,
        string $typeName,
        string $rawValue,
    ): self {
        return new self(
            exitCode: ExitCode::DATA_ERROR,
            message: \sprintf(
                'Value "%s" is not valid for parameter $%s of type %s',
                $rawValue,
                $parameterName,
                $typeName,
            ),
        );
    }

    public static function fromUnsupportedCommandParameterType(
        string $parameterName,
        string $typeName,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Parameter $%s has unsupported type %s',
                $parameterName,
                $typeName,
            ),
        );
    }

    public static function fromNoCommandGiven(): self
    {
        return new self(
            exitCode: ExitCode::USAGE,
            message: 'No command given',
        );
    }

    /**
     * @param list<string> $argv
     */
    public static function fromUnrecognizedCommand(
        array $argv,
    ): self {
        return new self(
            exitCode: ExitCode::COMMAND_NOT_FOUND,
            message: \sprintf(
                'Unrecognized command: %s',
                \join(' ', $argv),
            ),
        );
    }

    public static function fromDiscoveryDirectoryNotFound(
        string $directory,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command discovery directory "%s" does not exist',
                $directory,
            ),
            previous: $previous,
        );
    }

    public static function fromMultipleDefaultCommands(): self
    {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: 'Only one default command may be registered',
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromConflictingCommandKind(
        string $className,
        string $methodName,
    ): self {
        return new self(
            exitCode: ExitCode::CONFIG_ERROR,
            message: \sprintf(
                'Command %s::%s carries both #[Command] and #[DefaultCommand] attributes',
                $className,
                $methodName,
            ),
        );
    }
}
