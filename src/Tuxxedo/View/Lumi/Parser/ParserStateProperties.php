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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;

readonly class ParserStateProperties implements ParserStatePropertiesInterface
{
    /**
     * @param string[] $groupingStack
     * @param ExpressionNodeInterface[] $nodeStack
     * @param array<string, string|int|bool> $state
     */
    final public function __construct(
        public int $conditionDepth,
        public array $groupingStack,
        public array $nodeStack,
        public array $state,
    ) {
    }

    public static function fromState(
        ParserStateInterface $state,
    ): static {
        return new static(
            conditionDepth: $state->conditionDepth,
            groupingStack: $state->groupingStack,
            nodeStack: $state->nodeStack,
            state: $state->state,
        );
    }
}
