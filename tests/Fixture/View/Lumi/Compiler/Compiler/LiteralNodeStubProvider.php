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

namespace Fixture\View\Lumi\Compiler\Compiler;

use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\CompilerProviderInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\NodeCompilerHandler;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class LiteralNodeStubProvider implements CompilerProviderInterface
{
    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: LiteralNode::class,
            handler: static function (
                NodeInterface $node,
                CompilerInterface $compiler,
                NodeStreamInterface $stream,
            ): string {
                return \sprintf(
                    '<%s:%s>',
                    $node->type->name,
                    $node->operand,
                );
            },
        );
    }
}
