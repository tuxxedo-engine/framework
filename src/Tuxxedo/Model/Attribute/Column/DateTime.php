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

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface as TableColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Model\Attribute\ColumnFormatInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\Coercer\DateTimeCoercer;
use Tuxxedo\Validator\Rule\DateTime\DateTimeRule;
use Tuxxedo\Validator\RuleProviderInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class DateTime implements ColumnInterface, ColumnFormatInterface, RuleProviderInterface
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
        public DateFormat|string $format = DateFormat::DEFAULT,
        public ?string $name = null,
        public ?string $coercer = DateTimeCoercer::class,
        public ?string $behavior = null,
        public bool $nullable = false,
        public bool $primaryKey = false,
        public bool $unique = false,
        public ?string $default = null,
    ) {
        $this->coercerArguments = [
            'format' => $this->format,
        ];
    }

    public function getFormat(
        DialectInterface $dialect,
    ): string {
        return $this->format instanceof DateFormat
                ? $this->format->value
                : $this->format;
    }

    public function toRules(): iterable
    {
        yield new DateTimeRule(
            format: $this->format instanceof DateFormat
                ? $this->format->value
                : $this->format,
        );
    }

    public function toColumnType(
        CreateTableStatementInterface $statement,
        string $propertyName,
    ): TableColumnInterface {
        return $statement->dateTime(
            name: $this->name ?? $propertyName,
            nullable: $this->nullable,
            primaryKey: $this->primaryKey,
            unique: $this->unique,
            default: $this->default,
        );
    }
}
