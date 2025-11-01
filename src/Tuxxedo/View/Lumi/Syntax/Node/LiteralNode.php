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

namespace Tuxxedo\View\Lumi\Syntax\Node;

use Tuxxedo\View\Lumi\Syntax\Type;

readonly class LiteralNode implements ExpressionNodeInterface
{
    public array $scopes;

    public function __construct(
        public string $operand,
        public Type $type,
    ) {
        $this->scopes = [
            NodeScope::EXPRESSION,
            NodeScope::EXPRESSION_ASSIGN,
        ];
    }

    public static function createString(
        string $value,
    ): self {
        return new self(
            operand: $value,
            type: Type::STRING,
        );
    }

    public static function createInt(
        string|int $value,
    ): self {
        return new self(
            operand: \strval($value),
            type: Type::INT,
        );
    }

    public static function createFloat(
        string|float $value,
    ): self {
        return new self(
            operand: \strval($value),
            type: Type::FLOAT,
        );
    }

    public static function createBool(
        string|bool $value,
    ): self {
        if (\is_bool($value)) {
            $value = $value
                ? 'true'
                : 'false';
        }

        return new self(
            operand: $value,
            type: Type::BOOL,
        );
    }

    public static function createNull(): self
    {
        return new self(
            operand: 'null',
            type: Type::NULL,
        );
    }
}
