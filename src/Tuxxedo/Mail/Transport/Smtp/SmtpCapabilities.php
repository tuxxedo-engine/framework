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

namespace Tuxxedo\Mail\Transport\Smtp;

class SmtpCapabilities
{
    /**
     * @param array<string, list<string>> $features
     */
    public function __construct(
        public readonly array $features = [],
    ) {
    }

    public function supports(
        string $feature,
    ): bool {
        return \array_key_exists(\strtoupper($feature), $this->features);
    }

    /**
     * @return list<string>
     */
    public function getParams(
        string $feature,
    ): array {
        return $this->features[\strtoupper($feature)] ?? [];
    }

    /**
     * @param list<string> $lines
     */
    #[\NoDiscard]
    public static function parse(
        array $lines,
    ): self {
        $features = [];

        foreach (\array_slice($lines, 1) as $line) {
            $parts = \preg_split('/\s+/', \trim($line));

            if ($parts === false || $parts === [] || $parts[0] === '') {
                continue;
            }

            $name = \strtoupper($parts[0]);
            $features[$name] = \array_slice($parts, 1);
        }

        return new self(
            features: $features,
        );
    }
}
