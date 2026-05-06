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

namespace Unit\View\Lumi\Syntax\Node;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class LiteralNodeTest extends TestCase
{
    public function testCreateString(): void
    {
        $node = LiteralNode::createString('hello');

        self::assertSame('hello', $node->operand);
        self::assertSame(Type::STRING, $node->type);
    }

    public function testCreateIntFromInt(): void
    {
        $node = LiteralNode::createInt(42);

        self::assertSame('42', $node->operand);
        self::assertSame(Type::INT, $node->type);
    }

    public function testCreateIntFromString(): void
    {
        $node = LiteralNode::createInt('42');

        self::assertSame('42', $node->operand);
        self::assertSame(Type::INT, $node->type);
    }

    public function testCreateFloatFromFloat(): void
    {
        $node = LiteralNode::createFloat(3.14);

        self::assertSame('3.14', $node->operand);
        self::assertSame(Type::FLOAT, $node->type);
    }

    public function testCreateFloatFromString(): void
    {
        $node = LiteralNode::createFloat('3.14');

        self::assertSame('3.14', $node->operand);
        self::assertSame(Type::FLOAT, $node->type);
    }

    public function testCreateBoolFromTrue(): void
    {
        $node = LiteralNode::createBool(true);

        self::assertSame('true', $node->operand);
        self::assertSame(Type::BOOL, $node->type);
    }

    public function testCreateBoolFromFalse(): void
    {
        $node = LiteralNode::createBool(false);

        self::assertSame('false', $node->operand);
        self::assertSame(Type::BOOL, $node->type);
    }

    public function testCreateBoolFromString(): void
    {
        $node = LiteralNode::createBool('true');

        self::assertSame('true', $node->operand);
        self::assertSame(Type::BOOL, $node->type);
    }

    public function testCreateNull(): void
    {
        $node = LiteralNode::createNull();

        self::assertSame('null', $node->operand);
        self::assertSame(Type::NULL, $node->type);
    }

    public function testCreateFromNativeTypeWithString(): void
    {
        $node = LiteralNode::createFromNativeType('hello');

        self::assertSame('hello', $node->operand);
        self::assertSame(Type::STRING, $node->type);
    }

    public function testCreateFromNativeTypeWithInt(): void
    {
        $node = LiteralNode::createFromNativeType(42);

        self::assertSame('42', $node->operand);
        self::assertSame(Type::INT, $node->type);
    }

    public function testCreateFromNativeTypeWithFloat(): void
    {
        $node = LiteralNode::createFromNativeType(3.14);

        self::assertSame('3.14', $node->operand);
        self::assertSame(Type::FLOAT, $node->type);
    }

    public function testCreateFromNativeTypeWithBool(): void
    {
        $node = LiteralNode::createFromNativeType(true);

        self::assertSame('true', $node->operand);
        self::assertSame(Type::BOOL, $node->type);
    }

    public function testCreateFromNativeTypeWithNull(): void
    {
        $node = LiteralNode::createFromNativeType(null);

        self::assertSame('null', $node->operand);
        self::assertSame(Type::NULL, $node->type);
    }
}
