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

namespace Unit\Env;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Env\DotEnvParser;
use Tuxxedo\Env\EnvException;

class DotEnvParserTest extends TestCase
{
    private DotEnvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DotEnvParser();
    }

    public function testEmptyContentReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->parser->parse(
                contents: '',
                file: '.env',
            ),
        );
    }

    public function testWhitespaceOnlyContentReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->parser->parse(
                contents: "   \n\t\n\n",
                file: '.env',
            ),
        );
    }

    public function testCommentOnlyContentReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            $this->parser->parse(
                contents: "# just a comment\n# another\n",
                file: '.env',
            ),
        );
    }

    public function testParsesSimpleKeyValue(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: 'FOO=bar',
                file: '.env',
            ),
        );
    }

    public function testParsesMultipleKeyValues(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
                'BAZ' => 'qux',
            ],
            $this->parser->parse(
                contents: "FOO=bar\nBAZ=qux\n",
                file: '.env',
            ),
        );
    }

    public function testParsesEmptyValue(): void
    {
        self::assertSame(
            [
                'FOO' => '',
            ],
            $this->parser->parse(
                contents: 'FOO=',
                file: '.env',
            ),
        );
    }

    public function testSkipsBlankLinesBetweenPairs(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
                'BAZ' => 'qux',
            ],
            $this->parser->parse(
                contents: "FOO=bar\n\n\nBAZ=qux",
                file: '.env',
            ),
        );
    }

    public function testSkipsCommentLines(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: "# leading comment\nFOO=bar\n# trailing comment\n",
                file: '.env',
            ),
        );
    }

    public function testAllowsTrailingCommentAfterValue(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: 'FOO=bar # inline comment',
                file: '.env',
            ),
        );
    }

    public function testStripsUtf8Bom(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: "\xEF\xBB\xBFFOO=bar",
                file: '.env',
            ),
        );
    }

    public function testNormalizesCrlfLineEndings(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
                'BAZ' => 'qux',
            ],
            $this->parser->parse(
                contents: "FOO=bar\r\nBAZ=qux",
                file: '.env',
            ),
        );
    }

    public function testNormalizesCrLineEndings(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
                'BAZ' => 'qux',
            ],
            $this->parser->parse(
                contents: "FOO=bar\rBAZ=qux",
                file: '.env',
            ),
        );
    }

    public function testSkipsExportPrefix(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: 'export FOO=bar',
                file: '.env',
            ),
        );
    }

    public function testAllowsWhitespaceAroundEquals(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: 'FOO   =   bar',
                file: '.env',
            ),
        );
    }

    public function testCoercesTrueToBool(): void
    {
        self::assertSame(
            [
                'FLAG' => true,
            ],
            $this->parser->parse(
                contents: 'FLAG=true',
                file: '.env',
            ),
        );
    }

    public function testCoercesUppercaseTrueToBool(): void
    {
        self::assertSame(
            [
                'FLAG' => true,
            ],
            $this->parser->parse(
                contents: 'FLAG=TRUE',
                file: '.env',
            ),
        );
    }

    public function testCoercesFalseToBool(): void
    {
        self::assertSame(
            [
                'FLAG' => false,
            ],
            $this->parser->parse(
                contents: 'FLAG=false',
                file: '.env',
            ),
        );
    }

    public function testCoercesIntegerValue(): void
    {
        self::assertSame(
            [
                'NUM' => 42,
            ],
            $this->parser->parse(
                contents: 'NUM=42',
                file: '.env',
            ),
        );
    }

    public function testCoercesNegativeInteger(): void
    {
        self::assertSame(
            [
                'NUM' => -7,
            ],
            $this->parser->parse(
                contents: 'NUM=-7',
                file: '.env',
            ),
        );
    }

    public function testCoercesFloatValue(): void
    {
        self::assertSame(
            [
                'PI' => 3.14,
            ],
            $this->parser->parse(
                contents: 'PI=3.14',
                file: '.env',
            ),
        );
    }

    public function testCoercesScientificNotationFloat(): void
    {
        self::assertSame(
            [
                'BIG' => 1.5e10,
            ],
            $this->parser->parse(
                contents: 'BIG=1.5e10',
                file: '.env',
            ),
        );
    }

    public function testUnquotedValueRtrimsTrailingWhitespace(): void
    {
        self::assertSame(
            [
                'FOO' => 'bar',
            ],
            $this->parser->parse(
                contents: "FOO=bar   \n",
                file: '.env',
            ),
        );
    }

    public function testHashMidUnquotedValueIsLiteral(): void
    {
        self::assertSame(
            [
                'URL' => 'https://example.com#anchor',
            ],
            $this->parser->parse(
                contents: 'URL=https://example.com#anchor',
                file: '.env',
            ),
        );
    }

    public function testSingleQuotedPreservesLiteralContent(): void
    {
        self::assertSame(
            [
                'FOO' => '${BAR} \n literal',
            ],
            $this->parser->parse(
                contents: "FOO='\${BAR} \\n literal'",
                file: '.env',
            ),
        );
    }

    public function testSingleQuotedAllowsHashLiteral(): void
    {
        self::assertSame(
            [
                'FOO' => 'not # a comment',
            ],
            $this->parser->parse(
                contents: "FOO='not # a comment'",
                file: '.env',
            ),
        );
    }

    public function testSingleQuotedSpansMultipleLines(): void
    {
        self::assertSame(
            [
                'FOO' => "line1\nline2",
            ],
            $this->parser->parse(
                contents: "FOO='line1\nline2'",
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedPreservesLiteralUnlessEscaped(): void
    {
        self::assertSame(
            [
                'FOO' => 'hello world',
            ],
            $this->parser->parse(
                contents: 'FOO="hello world"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsNewlineEscape(): void
    {
        self::assertSame(
            [
                'FOO' => "line1\nline2",
            ],
            $this->parser->parse(
                contents: 'FOO="line1\nline2"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsCarriageReturnEscape(): void
    {
        self::assertSame(
            [
                'FOO' => "a\rb",
            ],
            $this->parser->parse(
                contents: 'FOO="a\rb"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsTabEscape(): void
    {
        self::assertSame(
            [
                'FOO' => "a\tb",
            ],
            $this->parser->parse(
                contents: 'FOO="a\tb"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsBackslashEscape(): void
    {
        self::assertSame(
            [
                'FOO' => 'a\\b',
            ],
            $this->parser->parse(
                contents: 'FOO="a\\\\b"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsQuoteEscape(): void
    {
        self::assertSame(
            [
                'FOO' => 'a"b',
            ],
            $this->parser->parse(
                contents: 'FOO="a\"b"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedInterpretsDollarEscape(): void
    {
        self::assertSame(
            [
                'FOO' => 'literal $VAR',
            ],
            $this->parser->parse(
                contents: 'FOO="literal \$VAR"',
                file: '.env',
            ),
        );
    }

    public function testDoubleQuotedSpansMultipleLines(): void
    {
        self::assertSame(
            [
                'FOO' => "a\nb",
            ],
            $this->parser->parse(
                contents: "FOO=\"a\nb\"",
                file: '.env',
            ),
        );
    }

    public function testInterpolatesPreviousKeyInDoubleQuoted(): void
    {
        self::assertSame(
            [
                'A' => 'hello',
                'B' => 'hello world',
            ],
            $this->parser->parse(
                contents: "A=hello\nB=\"\${A} world\"",
                file: '.env',
            ),
        );
    }

    public function testInterpolatesPreviousKeyInUnquoted(): void
    {
        self::assertSame(
            [
                'A' => 'hello',
                'B' => 'hello world',
            ],
            $this->parser->parse(
                contents: "A=hello\nB=\${A} world",
                file: '.env',
            ),
        );
    }

    public function testUnquotedBackslashDollarIsLiteralDollar(): void
    {
        self::assertSame(
            [
                'FOO' => '$LITERAL',
            ],
            $this->parser->parse(
                contents: 'FOO=\\$LITERAL',
                file: '.env',
            ),
        );
    }

    public function testInterpolatesIntValueAsString(): void
    {
        self::assertSame(
            [
                'N' => 42,
                'S' => 'value is 42',
            ],
            $this->parser->parse(
                contents: "N=42\nS=\"value is \${N}\"",
                file: '.env',
            ),
        );
    }

    public function testThrowsOnDuplicateKey(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: "FOO=one\nFOO=two",
            file: '.env',
        );
    }

    public function testThrowsOnEmptyKey(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: '=value',
            file: '.env',
        );
    }

    public function testThrowsOnKeyStartingWithDigit(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: '1FOO=bar',
            file: '.env',
        );
    }

    public function testThrowsOnKeyWithInvalidCharacter(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FO-O=bar',
            file: '.env',
        );
    }

    public function testThrowsOnMissingEquals(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO',
            file: '.env',
        );
    }

    public function testThrowsOnMissingEqualsAtEndOfLine(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: "FOO\nBAR=baz",
            file: '.env',
        );
    }

    public function testThrowsOnUnclosedSingleQuote(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: "FOO='unfinished",
            file: '.env',
        );
    }

    public function testThrowsOnUnclosedDoubleQuote(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="unfinished',
            file: '.env',
        );
    }

    public function testThrowsOnDanglingBackslashInDoubleQuote(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="ends with \\',
            file: '.env',
        );
    }

    public function testThrowsOnUnknownEscapeSequence(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="bad \x escape"',
            file: '.env',
        );
    }

    public function testThrowsOnUnexpectedCharacterAfterValue(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: "FOO='bar' x\n",
            file: '.env',
        );
    }

    public function testThrowsOnUnterminatedInterpolation(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="${UNCLOSED',
            file: '.env',
        );
    }

    public function testThrowsOnInterpolationContainingNewline(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: "FOO=\"\${BROKEN\n}\"",
            file: '.env',
        );
    }

    public function testThrowsOnInvalidInterpolationVariableName(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="${1BAD}"',
            file: '.env',
        );
    }

    public function testThrowsOnUnresolvedInterpolation(): void
    {
        $this->expectException(EnvException::class);

        $this->parser->parse(
            contents: 'FOO="${MISSING}"',
            file: '.env',
        );
    }

    public function testExceptionMessageIncludesFileAndLine(): void
    {
        try {
            $this->parser->parse(
                contents: "FOO=bar\nBAZ=qux\n1BAD=oops",
                file: 'config/.env',
            );

            self::fail('Expected EnvException');
        } catch (EnvException $exception) {
            self::assertStringContainsString(
                'config/.env',
                $exception->getMessage(),
            );

            self::assertStringContainsString(
                'line 3',
                $exception->getMessage(),
            );
        }
    }
}
