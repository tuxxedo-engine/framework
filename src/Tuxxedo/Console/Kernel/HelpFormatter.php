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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Console\Descriptor\ArgumentDescriptorInterface;
use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;
use Tuxxedo\Console\Descriptor\FlagDescriptorInterface;
use Tuxxedo\Console\Descriptor\OptionDescriptorInterface;
use Tuxxedo\Console\Output\OutputInterface;

class HelpFormatter implements HelpFormatterInterface
{
    /**
     * @param list<CommandDescriptorInterface> $commands
     */
    public function renderIndex(
        array $commands,
        OutputInterface $output,
    ): void {
        $output->line('Available commands:');
        $output->line();

        if ($commands === []) {
            $output->line('  (none registered)');

            return;
        }

        $paths = [];
        $maxPathLength = 0;

        foreach ($commands as $descriptor) {
            $path = \join(' ', $descriptor->path);
            $paths[] = [
                'path' => $path,
                'description' => $descriptor->description ?? '',
            ];

            $length = \strlen($path);

            if ($length > $maxPathLength) {
                $maxPathLength = $length;
            }
        }

        foreach ($paths as $entry) {
            $output->line(
                \sprintf(
                    '  %s  %s',
                    \str_pad($entry['path'], $maxPathLength),
                    $entry['description'],
                ),
            );
        }

        $output->line();
        $output->line('Run "<command> --help" for details on a specific command.');
    }

    public function render(
        CommandDescriptorInterface $descriptor,
        OutputInterface $output,
    ): void {
        $output->line($this->formatUsage($descriptor));
        $output->line();

        if ($descriptor->description !== null && $descriptor->description !== '') {
            $output->line($descriptor->description);
            $output->line();
        }

        if ($descriptor->arguments !== []) {
            $output->line('Arguments:');

            foreach ($descriptor->arguments as $argument) {
                $output->line($this->formatArgumentLine($argument));
            }

            $output->line();
        }

        if ($descriptor->options !== []) {
            $output->line('Options:');

            foreach ($descriptor->options as $option) {
                $output->line($this->formatOptionLine($option));
            }

            $output->line();
        }

        if ($descriptor->flags !== []) {
            $output->line('Flags:');

            foreach ($descriptor->flags as $flag) {
                $output->line($this->formatFlagLine($flag));
            }

            $output->line();
        }
    }

    private function formatUsage(
        CommandDescriptorInterface $descriptor,
    ): string {
        $parts = [
            'Usage:',
            \join(' ', $descriptor->path),
        ];

        foreach ($descriptor->flags as $flag) {
            $parts[] = $this->formatFlagToken($flag);
        }

        foreach ($descriptor->options as $option) {
            $parts[] = $this->formatOptionToken($option);
        }

        foreach ($descriptor->arguments as $argument) {
            $parts[] = $this->formatArgumentToken($argument);
        }

        return \join(' ', $parts);
    }

    private function formatFlagToken(
        FlagDescriptorInterface $flag,
    ): string {
        $names = $flag->short !== null
            ? \sprintf('--%s|-%s', $flag->name, $flag->short)
            : '--' . $flag->name;

        return \sprintf('[%s]', $names);
    }

    private function formatOptionToken(
        OptionDescriptorInterface $option,
    ): string {
        $names = $option->short !== null
            ? \sprintf('--%s|-%s', $option->name, $option->short)
            : '--' . $option->name;

        return \sprintf('[%s=<value>]', $names);
    }

    private function formatArgumentToken(
        ArgumentDescriptorInterface $argument,
    ): string {
        return $argument->hasDefault
            ? \sprintf('[<%s>]', $argument->name)
            : \sprintf('<%s>', $argument->name);
    }

    private function formatFlagLine(
        FlagDescriptorInterface $flag,
    ): string {
        $names = $flag->short !== null
            ? \sprintf('  --%s, -%s', $flag->name, $flag->short)
            : \sprintf('  --%s', $flag->name);

        if ($flag->description !== null && $flag->description !== '') {
            return \sprintf('%s  %s', $names, $flag->description);
        }

        return $names;
    }

    private function formatOptionLine(
        OptionDescriptorInterface $option,
    ): string {
        $names = $option->short !== null
            ? \sprintf('  --%s, -%s', $option->name, $option->short)
            : \sprintf('  --%s', $option->name);

        if ($option->description !== null && $option->description !== '') {
            return \sprintf('%s  %s', $names, $option->description);
        }

        return $names;
    }

    private function formatArgumentLine(
        ArgumentDescriptorInterface $argument,
    ): string {
        $line = '  ' . $argument->name;

        if ($argument->description !== null && $argument->description !== '') {
            $line .= '  ' . $argument->description;
        }

        if ($argument->hasDefault) {
            $line .= \sprintf(' (default: %s)', $this->formatDefault($argument->default));
        }

        return $line;
    }

    private function formatDefault(
        mixed $value,
    ): string {
        if ($value === null) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_string($value)) {
            return \sprintf('"%s"', $value);
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        return '(complex)';
    }
}
