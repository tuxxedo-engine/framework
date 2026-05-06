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
use Tuxxedo\View\Lumi\Compiler\Provider\PostNodeCompilerHandler;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class PostFooProvider implements CompilerProviderInterface
{
    public function __construct(
        public string $output = '/* post-foo */',
    ) {
    }

    public function augment(): \Generator
    {
        yield new PostNodeCompilerHandler(
            nodeClassName: FooNode::class,
            handler: fn (
                NodeInterface $node,
                CompilerInterface $compiler,
                NodeStreamInterface $stream,
            ): string => $this->output,
        );
    }
}
