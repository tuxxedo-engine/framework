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

namespace Tuxxedo\Database\Query\Statement\Table\Column;

use Tuxxedo\Database\Query\Dialect\DialectInterface;

// @todo Support native enums
class EnumerationColumn extends AbstractColumn
{
    /**
     * @var list<string>
     */
    public readonly array $values;

    /**
     * @param list<string> $values
     */
    public function __construct(
        string $name,
        array $values,
        bool $nullable = false,
        bool $unique = false,
        string|null $default = null,
    ) {
        $this->values = $values;

        parent::__construct(
            name: $name,
            nullable: $nullable,
            unique: $unique,
            default: $default,
        );
    }

    protected function renderType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? 'TEXT';
    }
}
