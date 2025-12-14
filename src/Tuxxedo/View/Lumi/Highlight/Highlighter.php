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

namespace Tuxxedo\View\Lumi\Highlight;

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeFactory;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeFactoryInterface;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\LumiNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\SymbolInterface;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Type;

class Highlighter implements HighlighterInterface
{
    private ThemeInterface $theme;
    private readonly EscaperInterface $escaper;
    private readonly ThemeFactoryInterface $themeFactory;

    public function __construct(
        ?EscaperInterface $escaper = null,
        ?ThemeFactoryInterface $themeFactory = null,
    ) {
        $this->escaper = $escaper ?? new Escaper();
        $this->themeFactory = $themeFactory ?? ThemeFactory::createDefault();
    }

    public function highlight(
        ThemeInterface|string $theme,
        NodeStreamInterface $stream,
    ): string {
        $source = '';
        $this->theme = \is_string($theme)
            ? $this->themeFactory->find($theme)
            : $theme;

        try {
            while (!$stream->eof()) {
                $source .= $this->highlightNode($stream->consume());
            }
        } finally {
            unset($this->theme);
        }

        return $source;
    }

    /**
     * @throws ParserException
     */
    private function highlightNode(
        NodeInterface $node,
    ): string {
        return match (true) {
            $node instanceof ArrayAccessNode => $this->highlightArrayAccessNode($node),
            $node instanceof ArrayItemNode => $this->highlightArrayItemNode($node),
            $node instanceof ArrayNode => $this->highlightArrayNode($node),
            $node instanceof AssignmentNode => $this->highlightAssignmentNode($node),
            $node instanceof BinaryOpNode => $this->highlightBinaryOpNode($node),
            $node instanceof BlockNode => $this->highlightBlockNode($node),
            $node instanceof BreakNode => $this->highlightBreakNode($node),
            $node instanceof CommentNode => $this->highlightCommentNode($node),
            $node instanceof ConcatNode => $this->highlightConcatNode($node),
            $node instanceof ConditionalNode => $this->highlightConditionalNode($node),
            $node instanceof ContinueNode => $this->highlightContinueNode($node),
            $node instanceof DeclareNode => $this->highlightDeclareNode($node),
            $node instanceof DoWhileNode => $this->highlightDoWhileNode($node),
            $node instanceof EchoNode => $this->highlightEchoNode($node),
            $node instanceof FilterOrBitwiseOrNode => $this->highlightFilterOrBitwiseOrNode($node),
            $node instanceof ForNode => $this->highlightForNode($node),
            $node instanceof FunctionCallNode => $this->highlightFunctionCallNode($node),
            $node instanceof GroupNode => $this->highlightGroupNode($node),
            $node instanceof IdentifierNode => $this->highlightIdentifierNode($node),
            $node instanceof LayoutNode => $this->highlightLayoutNode($node),
            $node instanceof LiteralNode => $this->highlightLiteralNode($node),
            $node instanceof LumiNode => $this->highlightLumiNode($node),
            $node instanceof MethodCallNode => $this->highlightMethodCallNode($node),
            $node instanceof PropertyAccessNode => $this->highlightPropertyAccessNode($node),
            $node instanceof TextNode => $this->highlightTextNode($node),
            $node instanceof UnaryOpNode => $this->highlightUnaryOpNode($node),
            $node instanceof WhileNode => $this->highlightWhileNode($node),
            default => throw HighlightException::fromUnknownHighlightNode($node),
        };
    }

    private function dye(
        ColorSlot $slot,
        SymbolInterface|string $text,
    ): string {
        return \sprintf(
            '<span style="color: %s;">%s</span>',
            $this->theme->color($slot),
            $text instanceof SymbolInterface
                ? $text->symbol()
                : $text,
        );
    }

    private function literalString(
        string $operand,
    ): string {
        $quote = \str_contains($operand, '\"')
            ? '"'
            : '\'';

        return $quote . $operand . $quote;
    }

    private function highlightArrayAccessNode(
        ArrayAccessNode $node,
    ): string {
        $key = $node->key !== null ?
            $this->highlightNode($node->key)
            : '';

        return $this->highlightNode($node->array) .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::LEFT_SQUARE_BRACKET) .
            $key .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::RIGHT_SQUARE_BRACKET);
    }

    private function highlightArrayItemNode(
        ArrayItemNode $node,
    ): string {
        $key = $node->key !== null
            ? $this->highlightNode($node->key) . $this->dye(ColorSlot::DELIMITER, CharacterSymbol::COLON) . ' '
            : '';

        return $key .
            $this->highlightNode($node->value);
    }

    private function highlightArrayNode(
        ArrayNode $node,
    ): string {
        $items = [];

        foreach ($node->items as $item) {
            $items[] = $this->highlightNode($item);
        }

        $items = \join(
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::COMMA->symbol() . ' '),
            $items,
        );

        return $this->dye(ColorSlot::DELIMITER, CharacterSymbol::LEFT_SQUARE_BRACKET) .
            $items .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::RIGHT_SQUARE_BRACKET);
    }

    private function highlightAssignmentNode(
        AssignmentNode $node,
    ): string {
        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'set') . ' ' .
            $this->highlightNode($node->name) . ' ' .
            $this->dye(ColorSlot::OPERATOR, $this->escaper->html($node->operator->symbol())) . ' ' .
            $this->highlightNode($node->value) .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightBinaryOpNode(
        BinaryOpNode $node,
    ): string {
        return $this->highlightNode($node->left) . ' ' .
            $this->dye(
                match ($node->operator) {
                    BinarySymbol::CONCAT => ColorSlot::CONCAT,
                    BinarySymbol::NULL_COALESCE => ColorSlot::NULL_COALESCE,
                    default => ColorSlot::OPERATOR,
                },
                $this->escaper->html($node->operator->symbol()),
            ) . ' ' .
            $this->highlightNode($node->right);
    }

    private function highlightBlockNode(
        BlockNode $node,
    ): string {
        $body = '';

        foreach ($node->body as $bodyNode) {
            $body .= $this->highlightNode($bodyNode);
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'block') . ' ' .
            $this->dye(ColorSlot::IDENTIFIER, $node->name) .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $body .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'endblock') .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightBreakNode(
        BreakNode $node,
    ): string {
        $count = $node->count > 0
            ? ' ' . $this->dye(ColorSlot::NUMBER, (string) $node->count)
            : '';

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'break') .
            $count .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightCommentNode(
        CommentNode $node,
    ): string {
        return $this->dye(
            ColorSlot::COMMENT,
            \sprintf(
                '{# %s #}',
                $this->escaper->html($node->text),
            ),
        );
    }

    private function highlightConditionalNode(
        ConditionalNode $node,
    ): string {
        $ifBody = '';
        $branches = '';
        $else = '';

        foreach ($node->body as $bodyNode) {
            $ifBody .= $this->highlightNode($bodyNode);
        }

        foreach ($node->branches as $branch) {
            $branchBody = '';

            foreach ($branch->body as $bodyNode) {
                $branchBody .= $this->highlightNode($bodyNode);
            }

            $branches .=
                $this->dye(ColorSlot::DELIMITER, '{% ') .
                $this->dye(ColorSlot::KEYWORD, 'elseif') . ' ' .
                $this->highlightNode($branch->operand) .
                $this->dye(ColorSlot::DELIMITER, ' %}') .
                $branchBody;
        }

        if (\sizeof($node->else) > 0) {
            $else = $this->dye(ColorSlot::DELIMITER, '{% ') .
                $this->dye(ColorSlot::KEYWORD, 'else') .
                $this->dye(ColorSlot::DELIMITER, ' %}');

            foreach ($node->else as $bodyNode) {
                $else .= $this->highlightNode($bodyNode);
            }
        }

        return
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'if') . ' ' .
            $this->highlightNode($node->operand) .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $ifBody .
            $branches .
            $else .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'endif') .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightConcatNode(
        ConcatNode $node,
    ): string {
        $output = '';
        $separator = ' ' . $this->dye(ColorSlot::CONCAT, BinarySymbol::CONCAT) . ' ';

        foreach ($node->operands as $index => $operand) {
            if ($index > 0) {
                $output .= $separator;
            }

            $output .= $this->highlightNode($operand);
        }

        return $output;
    }

    private function highlightContinueNode(
        ContinueNode $node,
    ): string {
        $count = $node->count > 0
            ? ' '.$this->dye(ColorSlot::NUMBER, (string) $node->count)
            : '';

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'continue') .
            $count .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightDeclareNode(
        DeclareNode $node,
    ): string {
        if (\str_contains($node->directive->operand, '.')) {
            [$left, $right] = \explode('.', $node->directive->operand, 2);

            $directive = $this->dye(ColorSlot::IDENTIFIER, $left) .
                $this->dye(ColorSlot::DELIMITER, '.') .
                $this->dye(ColorSlot::IDENTIFIER, $right);
        } else {
            $directive = $this->dye(ColorSlot::IDENTIFIER, $node->directive->operand);
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'declare') . ' ' .
            $directive . ' ' .
            $this->dye(ColorSlot::OPERATOR, AssignmentSymbol::ASSIGN) . ' ' .
            $this->highlightNode($node->value) .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightDoWhileNode(
        DoWhileNode $node,
    ): string {
        $body = '';

        foreach ($node->body as $bodyNode) {
            $body .= $this->highlightNode($bodyNode);
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'do') .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $body .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'while') . ' ' .
            $this->highlightNode($node->operand) .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightEchoNode(
        EchoNode $node,
    ): string {
        return $this->dye(ColorSlot::DELIMITER, '{{ ') .
            $this->highlightNode($node->operand) .
            $this->dye(ColorSlot::DELIMITER, ' }}');
    }

    private function highlightFilterOrBitwiseOrNode(
        FilterOrBitwiseOrNode $node,
    ): string {
        if ($node->right instanceof IdentifierNode) {
            return $this->highlightNode($node->left) . ' ' .
                $this->dye(ColorSlot::PIPE, BinarySymbol::BITWISE_OR) . ' ' .
                $this->dye(ColorSlot::FILTER_NAME, $node->right->name);
        }

        return $this->highlightNode($node->left) . ' ' .
            $this->dye(ColorSlot::OPERATOR, BinarySymbol::BITWISE_OR) . ' ' .
            $this->highlightNode($node->right);
    }

    private function highlightForNode(
        ForNode $node,
    ): string {
        $body = '';
        $key = $node->key !== null
            ? $this->dye(ColorSlot::DELIMITER, CharacterSymbol::COMMA) . $this->highlightNode($node->key) . ' '
            : '';

        foreach ($node->body as $bodyNode) {
            $body .= $this->highlightNode($bodyNode);
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'for') . ' ' .
            $this->highlightNode($node->value) . ' ' .
            $key .
            $this->dye(ColorSlot::KEYWORD, 'in') . ' ' .
            $this->highlightNode($node->iterator) .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $body .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'endfor') .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightFunctionCallNode(
        FunctionCallNode $node,
    ): string {
        $arguments = [];
        $name = $node->name instanceof IdentifierNode
            ? $this->dye(ColorSlot::FUNCTION_NAME, $node->name->name)
            : $this->highlightNode($node->name);

        foreach ($node->arguments as $argument) {
            $arguments[] = $this->highlightNode($argument);
        }

        $arguments = \join(
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::COMMA) . ' ',
            $arguments,
        );

        return $name .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::LEFT_PARENTHESIS) .
            $arguments .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::RIGHT_PARENTHESIS);
    }

    private function highlightGroupNode(
        GroupNode $node,
    ): string {
        return $this->dye(ColorSlot::DELIMITER, CharacterSymbol::LEFT_PARENTHESIS) .
            $this->highlightNode($node->operand) .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::RIGHT_PARENTHESIS);
    }

    private function highlightIdentifierNode(
        IdentifierNode $node,
    ): string {
        return $this->dye(ColorSlot::IDENTIFIER, $node->name);
    }

    private function highlightLayoutNode(
        LayoutNode $node,
    ): string {
        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'layout') . ' ' .
            $this->highlightNode(
                node: LiteralNode::createString($node->file),
            ) .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightLumiNode(
        LumiNode $node,
    ): string {
        $theme = '';

        if ($node->theme !== '') {
            $theme = ' ' .
                $this->dye(ColorSlot::IDENTIFIER, $this->escaper->html($node->theme));
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'lumi') .
            $theme .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $this->dye(ColorSlot::TEXT, $this->escaper->html($node->sourceCode)) .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'endlumi') .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }

    private function highlightLiteralNode(
        LiteralNode $node,
    ): string {
        if ($node->type === Type::INT || $node->type === Type::FLOAT) {
            return $this->dye(ColorSlot::NUMBER, $node->operand);
        } elseif ($node->type === Type::BOOL) {
            return $this->dye(ColorSlot::BOOL, $node->operand);
        } elseif ($node->type === Type::NULL) {
            return $this->dye(ColorSlot::NULL, $node->operand);
        } else {
            return $this->dye(ColorSlot::STRING, $this->escaper->html($this->literalString($node->operand)));
        }
    }

    private function highlightMethodCallNode(
        MethodCallNode $node,
    ): string {
        $arguments = [];
        $operator = $node->nullSafe
            ? BinarySymbol::NULL_SAFE_ACCESS->symbol()
            : CharacterSymbol::DOT->symbol();

        foreach ($node->arguments as $argument) {
            $arguments[] = $this->highlightNode($argument);
        }

        return $this->highlightNode($node->caller) .
            $this->dye(ColorSlot::DELIMITER, $operator) .
            $this->dye(ColorSlot::MEMBER_NAME, $node->name) .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::LEFT_PARENTHESIS) .
            \join(
                $this->dye(ColorSlot::DELIMITER, CharacterSymbol::COMMA) . ' ',
                $arguments,
            ) .
            $this->dye(ColorSlot::DELIMITER, CharacterSymbol::RIGHT_PARENTHESIS);
    }

    private function highlightPropertyAccessNode(
        PropertyAccessNode $node,
    ): string {
        $operator = $node->nullSafe
            ? BinarySymbol::NULL_SAFE_ACCESS->symbol()
            : CharacterSymbol::DOT->symbol();

        return $this->highlightNode($node->accessor) .
            $this->dye(ColorSlot::DELIMITER, $operator) .
            $this->dye(ColorSlot::MEMBER_NAME, $node->property);
    }

    private function highlightTextNode(
        TextNode $node,
    ): string {
        if ($node->context === TextContext::RAW) {
            return $this->dye(ColorSlot::DELIMITER, '{% ') .
                $this->dye(ColorSlot::KEYWORD, 'raw') .
                $this->dye(ColorSlot::DELIMITER, ' %}') .
                $this->dye(ColorSlot::TEXT, $this->escaper->html($node->text)) .
                $this->dye(ColorSlot::DELIMITER, '{% ') .
                $this->dye(ColorSlot::KEYWORD, 'endraw') .
                $this->dye(ColorSlot::DELIMITER, ' %}');
        }

        return $this->dye(ColorSlot::TEXT, $this->escaper->html($node->text));
    }

    private function highlightUnaryOpNode(
        UnaryOpNode $node,
    ): string {
        $expression = $this->highlightNode($node->operand);
        $operator = $this->dye(ColorSlot::OPERATOR, $this->escaper->html($node->operator->symbol()));

        return $node->operator->isPost()
            ? $expression . $operator
            : $operator . $expression;
    }

    private function highlightWhileNode(
        WhileNode $node,
    ): string {
        $body = '';

        foreach ($node->body as $bodyNode) {
            $body .= $this->highlightNode($bodyNode);
        }

        return $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'while') . ' ' .
            $this->highlightNode($node->operand) .
            $this->dye(ColorSlot::DELIMITER, ' %}') .
            $body .
            $this->dye(ColorSlot::DELIMITER, '{% ') .
            $this->dye(ColorSlot::KEYWORD, 'endwhile') .
            $this->dye(ColorSlot::DELIMITER, ' %}');
    }
}
