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

namespace Tuxxedo\View\Lumi\Compiler\Provider;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\BuiltinNodeKinds;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;

class TextCompilerProvider implements CompilerProviderInterface
{
    private function compileText(
        TextNode $node,
        CompilerInterface $compiler,
    ): string {
        return $this->stripPhpOpeningTag($node->text);
    }

    private function compileComment(
        CommentNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($compiler->state->directives->asBool('lumi.compiler_strip_comments')) {
            return '';
        }

        $commentary = '';
        $lines = \preg_split('/\n/u', $node->text);

        if ($lines !== false) {
            foreach ($lines as $line) {
                $commentary .= \sprintf(
                    "<?php // %s ?>\n",
                    $this->stripPhpEndingTag(\mb_trim($line)),
                );
            }
        }

        return $commentary;
    }

    private function compileEcho(
        EchoNode $node,
        CompilerInterface $compiler,
    ): string {
        $value = $compiler->compileExpression($node->operand);

        // @todo Fix the include call check
        if (
            $compiler->state->directives->asBool('lumi.autoescape')
            //            !(
            //                $node->operand instanceof FunctionCallNode &&
            //                \mb_strtolower($node->operand->name) === 'include'
            //            )
        ) {
            return \sprintf(
                '<?= $this->filter(%s, \'escape_html\'); ?>',
                $value,
            );
        }

        return \sprintf(
            '<?= %s; ?>',
            $value,
        );
    }

    private function compileDeclare(
        DeclareNode $node,
        CompilerInterface $compiler,
    ): string {
        $oldState = $compiler->state->swap(BuiltinNodeKinds::EXPRESSION->name);

        $compiler->state->directives->set(
            $node->directive->operand,
            match ($node->value->type) {
                NativeType::STRING => $node->value->operand,
                NativeType::INT => \intval($node->value->operand),
                NativeType::FLOAT => \floatval($node->value->operand),
                NativeType::BOOL => $node->value->operand === 'true',
                NativeType::NULL => null,
            },
        );

        $output = \sprintf(
            '<?php $this->directive(%s, %s); ?>',
            $compiler->compileNode($node->directive),
            $compiler->compileNode($node->value),
        );

        $compiler->state->swap($oldState);

        return $output;
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: TextNode::class,
            handler: $this->compileText(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: CommentNode::class,
            handler: $this->compileComment(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: EchoNode::class,
            handler: $this->compileEcho(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: DeclareNode::class,
            handler: $this->compileDeclare(...),
        );
    }

    private function stripPhpOpeningTag(string $code): string
    {
        return \preg_replace('/\s*<\?\s*/ui', '&lt;?', $code) ?? throw CompilerException::fromCannotEscapePhp();
    }

    private function stripPhpEndingTag(string $code): string
    {
        return \preg_replace('/\s*\?>\s*$/u', '', $code) ?? throw CompilerException::fromCannotEscapePhp();
    }
}
