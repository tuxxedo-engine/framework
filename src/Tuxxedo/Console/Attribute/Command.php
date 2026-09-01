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

namespace Tuxxedo\Console\Attribute;

use Tuxxedo\Console\ConsoleException;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
readonly class Command
{
    /**
     * @var list<string>
     */
    public array $path;

    /**
     * @param string|list<string> $name
     */
    public function __construct(
        string|array $name,
        public ?string $description = null,
    ) {
        $this->path = self::normalizePath($name);
    }

    /**
     * @param string|list<string> $name
     *
     * @return list<string>
     */
    private static function normalizePath(
        string|array $name,
    ): array {
        if (\is_string($name)) {
            $split = \preg_split(
                pattern: '/\s+/',
                subject: \trim($name),
            );

            if ($split === false) {
                throw ConsoleException::fromCommandNameParseFailure();
            }

            $name = $split;
        }

        $tokens = [];

        foreach ($name as $segment) {
            $segment = \trim($segment);

            if ($segment === '') {
                continue;
            }

            $tokens[] = $segment;
        }

        if ($tokens === []) {
            throw ConsoleException::fromEmptyCommandName();
        }

        return $tokens;
    }
}
