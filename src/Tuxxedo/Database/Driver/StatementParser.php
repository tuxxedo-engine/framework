<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Database\Dialect\DialectInterface;
use Tuxxedo\Database\SqlException;

class StatementParser implements StatementParserInterface
{
    public function __construct(
        public readonly DialectInterface $dialect,
    ) {
    }

    public function parse(
        string $sql,
        array $parameters = [],
    ): StatementParserResultInterface {
        $buffer = '';
        $slot = 1;
        $position = 0;
        $bindings = [];

        $length = \strlen($sql);
        $isAscii = $length === \mb_strlen($sql, 'UTF-8');

        while ($position < $length) {
            $byte = $sql[$position];

            if (
                !$isAscii &&
                \ord($byte) >= 0x80
            ) {
                $buffer .= $byte;
                $position++;

                continue;
            }

            if (\in_array($byte, $this->dialect->quotations, true)) {
                $quoteClose = $byte;
                $buffer .= $byte;

                $position++;

                while ($position < $length) {
                    $queryByte = $sql[$position];

                    if (
                        !$isAscii &&
                        \ord($queryByte) >= 0x80
                    ) {
                        $buffer .= $queryByte;

                        $position++;

                        continue;
                    }

                    $buffer .= $queryByte;
                    $position++;

                    if ($queryByte === $quoteClose) {
                        if (
                            $quoteClose === "'" &&
                            $position < $length &&
                            $sql[$position] === "'"
                        ) {
                            $buffer .= "'";

                            $position++;

                            continue;
                        }

                        break;
                    }
                }

                continue;
            }

            if (
                $byte === ':' &&
                $position + 1 < $length
            ) {
                $next = $sql[$position + 1];

                if (\preg_match('/^[a-zA-Z0-9_]$/', $next) === 1) {
                    $nameStart = $position + 1;
                    $nameEnd = $nameStart;

                    while (
                        $nameEnd < $length &&
                        \preg_match('/^[a-zA-Z0-9_]$/', $sql[$nameEnd]) === 1
                    ) {
                        $nameEnd++;
                    }

                    $name = \substr($sql, $nameStart, $nameEnd - $nameStart);
                    $isArraySyntax = false;

                    if (
                        $nameEnd + 1 < $length &&
                        $sql[$nameEnd] === '[' &&
                        $sql[$nameEnd + 1] === ']'
                    ) {
                        $isArraySyntax = true;
                        $nameEnd += 2;
                    }

                    $position = $nameEnd;

                    if (!\array_key_exists($name, $parameters)) {
                        throw SqlException::fromUnboundPlaceholder(
                            name: $name,
                        );
                    }

                    $value = $parameters[$name];

                    if ($isArraySyntax) {
                        if (!\is_array($value) || \sizeof($value) === 0) {
                            throw SqlException::fromPlaceholderArrayInvalidValue(
                                name: $name,
                            );
                        }

                        $slots = [];

                        foreach ($value as $element) {
                            $slots[] = $this->dialect->placeholder($slot);
                            $bindings[] = $element;

                            $slot++;
                        }

                        $buffer .= \implode(', ', $slots);

                        continue;
                    }

                    if (\is_array($value)) {
                        throw SqlException::fromPlaceholderArrayWrongSyntax(
                            name: $name,
                        );
                    }

                    $buffer .= $this->dialect->placeholder($slot);
                    $bindings[] = $value;

                    $slot++;

                    continue;
                }
            }

            $buffer .= $byte;

            $position++;
        }

        return new StatementParserResult(
            sql: $buffer,
            bindings: $bindings,
        );
    }
}
