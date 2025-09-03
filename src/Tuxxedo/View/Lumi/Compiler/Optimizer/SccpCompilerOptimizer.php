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

namespace Tuxxedo\View\Lumi\Compiler\Optimizer;

use Tuxxedo\View\Lumi\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Node\TextNode;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class SccpCompilerOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $node = $this->optimizeNode($stream->current());

            if ($node !== null) {
                $nodes[] = $node;
            }

            $stream->consume();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    private function optimizeNode(
        NodeInterface $node,
    ): ?NodeInterface {
        return match (true) {
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof EchoNode => $this->optimizeEcho($node),
            default => $node,
        };
    }

    private function optimizeEcho(
        EchoNode $node,
    ): ?NodeInterface {
        if ($node->operand instanceof LiteralNode) {
            if ($node->operand->type === NodeNativeType::STRING) {
                if ($node->operand->operand === '') {
                    return null;
                } elseif (!$this->directives->asBool('lumi.autoescape')) {
                    return new TextNode(
                        text: $node->operand->operand,
                    );
                }

                return $node;
            }

            $value = match ($node->operand->type) {
                NodeNativeType::NULL => null,
                NodeNativeType::BOOL => \boolval($node->operand->operand),
                NodeNativeType::INT => \intval($node->operand->operand),
                NodeNativeType::FLOAT => \floatval($node->operand->operand),
            };

            if ($value !== null) {
                $value = new TextNode(
                    text: (string) $value,
                );
            }

            return $value;
        }

        return $node;
    }
}
