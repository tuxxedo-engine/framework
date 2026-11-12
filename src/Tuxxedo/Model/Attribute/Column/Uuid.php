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
use Tuxxedo\Model\Behavior\UuidV4Behavior;
use Tuxxedo\Model\Behavior\UuidV7Behavior;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Validator\Rule\Uuid\UuidRule;
use Tuxxedo\Validator\Rule\UuidV4\UuidV4Rule;
use Tuxxedo\Validator\Rule\UuidV7\UuidV7Rule;
use Tuxxedo\Validator\RuleProviderInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Uuid implements ColumnInterface, ColumnLengthInterface, RuleProviderInterface
{
    public int $length;

    /**
     * @var array<string, mixed>
     */
    public array $coercerArguments;

    /**
     * @var class-string<BehaviorInterface>|null
     */
    public ?string $behavior;

    /**
     * @param class-string<CoercerInterface>|null $coercer
     * @param class-string<BehaviorInterface>|null $behavior
     */
    public function __construct(
        public UuidVersion $version = UuidVersion::ANY,
        public ?string $name = null,
        public ?string $coercer = null,
        ?string $behavior = null,
        public bool $nullable = false,
        public bool $primaryKey = false,
        public bool $unique = false,
        public ?string $default = null,
    ) {
        $this->length = 36;
        $this->coercerArguments = [];
        $this->behavior = $behavior ?? match ($primaryKey ? $version : UuidVersion::ANY) {
            UuidVersion::V4 => UuidV4Behavior::class,
            UuidVersion::V7 => UuidV7Behavior::class,
            UuidVersion::ANY => null,
        };
    }

    public function toRules(): iterable
    {
        yield match ($this->version) {
            UuidVersion::ANY => new UuidRule(),
            UuidVersion::V4 => new UuidV4Rule(),
            UuidVersion::V7 => new UuidV7Rule(),
        };
    }

    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $columnName,
    ): TableColumnInterface {
        return $statement->uuid(
            name: $this->name ?? $columnName,
            nullable: $this->nullable,
            primaryKey: $this->primaryKey,
            unique: $this->unique,
            default: $this->default,
        );
    }
}
