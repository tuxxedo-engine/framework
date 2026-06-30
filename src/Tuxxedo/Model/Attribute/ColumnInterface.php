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

namespace Tuxxedo\Model\Attribute;

use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface as TableColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\ModelException;

interface ColumnInterface
{
    public ?string $name {
        get;
    }

    /**
     * @var class-string<CoercerInterface>|null
     */
    public ?string $coercer {
        get;
    }

    /**
     * @var class-string<BehaviorInterface>|null
     */
    public ?string $behavior {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $coercerArguments {
        get;
    }

    /**
     * @throws ModelException
     */
    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $propertyName,
    ): TableColumnInterface;
}
