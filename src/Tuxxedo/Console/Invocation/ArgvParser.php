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

namespace Tuxxedo\Console\Invocation;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\Descriptor\OptionDescriptorInterface;

class ArgvParser implements ArgvParserInterface
{
    public function parse(
        array $argv,
        CommandDescriptorInterface $descriptor,
    ): ParsedArgvInterface {
        $optionsByLong = [];
        $optionsByShort = [];

        foreach ($descriptor->options as $option) {
            $optionsByLong[$option->name] = $option;

            if ($option->short !== null) {
                $optionsByShort[$option->short] = $option;
            }
        }

        $flagsByLong = [];
        $flagsByShort = [];

        foreach ($descriptor->flags as $flag) {
            $flagsByLong[$flag->name] = $flag;

            if ($flag->short !== null) {
                $flagsByShort[$flag->short] = $flag;
            }
        }

        $positionals = [];
        $options = [];
        $flags = [];
        $sawTerminator = false;
        $count = \sizeof($argv);
        $i = 0;

        while ($i < $count) {
            $token = $argv[$i];

            if ($sawTerminator) {
                $positionals[] = $token;
                $i++;

                continue;
            }

            if ($token === '--') {
                $sawTerminator = true;
                $i++;

                continue;
            }

            if (\str_starts_with($token, '--')) {
                $rest = \substr($token, 2);
                $equalsPos = \strpos($rest, '=');

                if ($equalsPos !== false) {
                    $name = \substr($rest, 0, $equalsPos);
                    $value = \substr($rest, $equalsPos + 1);

                    if (isset($flagsByLong[$name])) {
                        throw ConsoleException::fromFlagWithValue($name);
                    }

                    if (!isset($optionsByLong[$name])) {
                        throw ConsoleException::fromUnknownOption($name);
                    }

                    $this->appendOptionValue(
                        option: $optionsByLong[$name],
                        value: $value,
                        options: $options,
                    );

                    $i++;

                    continue;
                }

                $name = $rest;

                if (isset($flagsByLong[$name])) {
                    $flags[$name] = true;
                    $i++;

                    continue;
                }

                if (isset($optionsByLong[$name])) {
                    if ($i + 1 >= $count) {
                        throw ConsoleException::fromMissingOptionValue($name);
                    }

                    $this->appendOptionValue(
                        option: $optionsByLong[$name],
                        value: $argv[$i + 1],
                        options: $options,
                    );

                    $i += 2;

                    continue;
                }

                throw ConsoleException::fromUnknownOption($name);
            }

            if (\str_starts_with($token, '-') && $token !== '-') {
                $chars = \substr($token, 1);
                $length = \strlen($chars);
                $j = 0;
                $consumedNext = false;

                while ($j < $length) {
                    $c = $chars[$j];

                    if (isset($flagsByShort[$c])) {
                        $flag = $flagsByShort[$c];
                        $flags[$flag->name] = true;
                        $j++;

                        continue;
                    }

                    if (isset($optionsByShort[$c])) {
                        $option = $optionsByShort[$c];
                        $remaining = \substr($chars, $j + 1);

                        if ($remaining !== '') {
                            $this->appendOptionValue(
                                option: $option,
                                value: $remaining,
                                options: $options,
                            );

                            break;
                        }

                        if ($i + 1 >= $count) {
                            throw ConsoleException::fromMissingOptionValue($option->name);
                        }

                        $this->appendOptionValue(
                            option: $option,
                            value: $argv[$i + 1],
                            options: $options,
                        );

                        $consumedNext = true;

                        break;
                    }

                    throw ConsoleException::fromUnknownOption($c);
                }

                $i += $consumedNext
                    ? 2
                    : 1;

                continue;
            }

            $positionals[] = $token;
            $i++;
        }

        return new ParsedArgv(
            positionals: $positionals,
            options: $options,
            flags: $flags,
        );
    }

    /**
     * @param array<string, list<string>> $options
     */
    private function appendOptionValue(
        OptionDescriptorInterface $option,
        string $value,
        array &$options,
    ): void {
        if (!$option->repeatable && isset($options[$option->name])) {
            throw ConsoleException::fromDuplicateOption($option->name);
        }

        $options[$option->name][] = $value;
    }
}
