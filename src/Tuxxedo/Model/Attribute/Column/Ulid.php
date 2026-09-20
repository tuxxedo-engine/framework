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
use Tuxxedo\Model\Behavior\UlidBehavior;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Validator\Rule\Ulid\UlidRule;
use Tuxxedo\Validator\RuleProviderInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class Ulid implements ColumnInterface, ColumnLengthInterface, RuleProviderInterface
{
    public readonly int $length;

    /**
     * @var array<string, mixed>
     */
    public readonly array $coercerArguments;

    /**
     * @var class-string<BehaviorInterface>|null
     */
    public readonly ?string $behavior;

    /**
     * @param class-string<CoercerInterface>|null $coercer
     * @param class-string<BehaviorInterface>|null $behavior
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $coercer = null,
        ?string $behavior = null,
        public readonly bool $nullable = false,
        public readonly bool $primaryKey = false,
        public readonly bool $unique = false,
        public readonly ?string $default = null,
    ) {
        $this->length = 26;
        $this->coercerArguments = [];
        $this->behavior = $behavior ?? ($primaryKey
            ? UlidBehavior::class
            : null);
    }

    public function toRules(): iterable
    {
        yield new UlidRule();
    }

    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $propertyName,
    ): TableColumnInterface {
        return $statement->char(
            name: $this->name ?? $propertyName,
            length: $this->length,
            nullable: $this->nullable,
            primaryKey: $this->primaryKey,
            unique: $this->unique,
            default: $this->default,
        );
    }
}
