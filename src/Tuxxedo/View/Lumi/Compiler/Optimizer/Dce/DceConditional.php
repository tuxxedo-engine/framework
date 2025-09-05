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

namespace Tuxxedo\View\Lumi\Compiler\Optimizer\Dce;

use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;

class DceConditional
{
    /**
     * @var array<int, bool>
     */
    public private(set) array $eliminateBranches = [];

    public private(set) ?int $newElse = null;

    final private function __construct()
    {
    }

    public static function fromNode(
        ConditionalNode $node,
    ): static {
        $self = new static();

        if (\sizeof($node->branches) > 0) {
            foreach (\array_keys($node->branches) as $index) {
                $self->eliminateBranches[$index] = false;
            }
        }

        return $self;
    }

    public function eliminateBranch(
        int $index,
    ): void {
        $this->eliminateBranches[$index] = true;
    }

    public function newElse(
        int $index,
    ): void {
        $this->newElse = $index;
    }

    public function newIf(): ?int
    {
        if (\sizeof($this->eliminateBranches) === 0) {
            return null;
        }

        foreach ($this->eliminateBranches as $index => $status) {
            if ($status === false) {
                return $index;
            }
        }

        return null;
    }
}
