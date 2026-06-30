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
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\ColumnLengthInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Varchar implements ColumnInterface, ColumnLengthInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $coercerArguments;

    /**
     * @param class-string<CoercerInterface>|null $coercer
     * @param class-string<BehaviorInterface>|null $behavior
     */
    public function __construct(
        public int $length = 255,
        public ?string $name = null,
        public ?string $coercer = null,
        public ?string $behavior = null,
    ) {
        $this->coercerArguments = [];
    }

    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $propertyName,
    ): TableColumnInterface {
        return $statement->varchar(
            name: $this->name ?? $propertyName,
            length: $this->length,
        );
    }
}
