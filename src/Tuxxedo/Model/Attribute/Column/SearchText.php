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

namespace Tuxxedo\Model\Attribute\Column;

use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface as TableColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Model\Behavior\SearchTextBehavior;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class SearchText extends Text
{
    /**
     * @param non-empty-list<string> $sources
     */
    public function __construct(
        public array $sources,
        ?string $name = null,
    ) {
        parent::__construct(
            name: $name,
            behavior: SearchTextBehavior::class,
        );
    }

    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $propertyName,
    ): TableColumnInterface {
        $column = parent::toColumnType(
            statement: $statement,
            propertyName: $propertyName,
        );

        $statement->fullTextIndex($this->name ?? $propertyName);

        return $column;
    }
}
