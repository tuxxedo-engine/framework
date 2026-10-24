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

namespace Tuxxedo\View\Lumi\Optimizer\Scope;

use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Scope implements ScopeInterface
{
    public readonly EvaluatorInterface $evaluator;

    public private(set) array $variables = [];

    public function __construct(
        ?EvaluatorInterface $evaluator = null,
    ) {
        $this->evaluator = $evaluator ?? new Evaluator();
    }

    public function __clone(): void
    {
        foreach ($this->variables as $name => $variable) {
            $this->variables[$name] = Variable::fromCopy($this, $variable);
        }
    }

    public function assign(
        AssignmentNode $node,
    ): void {
        if (!$node->name instanceof IdentifierNode) {
            return;
        }

        if ($node->operator !== AssignmentSymbol::ASSIGN) {
            if (!\array_key_exists($node->name->name, $this->variables)) {
                $this->create($node->name);

                return;
            }

            $this->variables[$node->name->name]->mutate(
                scope: $this,
                value: $node->value,
                operator: $node->operator,
            );
        } else {
            $this->variables[$node->name->name] = Variable::fromNewAssign(
                scope: $this,
                node: $node,
                name: $node->name,
            );
        }
    }

    public function create(
        IdentifierNode|string $name,
    ): void {
        if (!\is_string($name)) {
            $name = $name->name;
        }

        $this->variables[$name] = Variable::fromVarying($this, $name);
    }

    public function get(
        IdentifierNode|string $name,
    ): VariableInterface {
        if (!\is_string($name)) {
            $name = $name->name;
        }

        return $this->variables[$name] ??= Variable::fromUndefined($this, $name);
    }

    public function exists(
        IdentifierNode|string $name,
    ): bool {
        if (!\is_string($name)) {
            $name = $name->name;
        }

        return \array_key_exists($name, $this->variables);
    }

    public function join(
        ScopeInterface ...$paths,
    ): void {
        $names = [];

        foreach ($paths as $path) {
            foreach ($path->variables as $variable) {
                $names[$variable->name] = true;
            }
        }

        foreach (\array_keys($names) as $name) {
            $undefined = true;
            $constant = $paths[0]->variables[$name] ?? null;

            foreach ($paths as $path) {
                $variable = $path->variables[$name] ?? null;

                if (($variable->lattice ?? Lattice::UNDEF) !== Lattice::UNDEF) {
                    $undefined = false;
                }

                if ($constant !== null && !self::isSameConstant($constant, $variable)) {
                    $constant = null;
                }
            }

            if ($undefined) {
                unset($this->variables[$name]);
            } elseif ($constant !== null) {
                $this->variables[$name] = Variable::fromCopy($this, $constant);
            } else {
                $this->variables[$name] = Variable::fromVarying($this, $name);
            }
        }
    }

    public function merge(
        ScopeInterface $scope,
    ): self {
        foreach ($scope->variables as $variable) {
            if (\array_key_exists($variable->name, $this->variables)) {
                $this->variables[$variable->name] = Variable::fromExisting($this, $variable);
            }
        }

        return $this;
    }

    private static function isSameConstant(
        VariableInterface $constant,
        ?VariableInterface $variable,
    ): bool {
        return $variable !== null &&
            $constant->lattice === Lattice::CONST &&
            $variable->lattice === Lattice::CONST &&
            $constant->value instanceof LiteralNode &&
            $variable->value instanceof LiteralNode &&
            $constant->value->type === $variable->value->type &&
            $constant->value->operand === $variable->value->operand;
    }
}
