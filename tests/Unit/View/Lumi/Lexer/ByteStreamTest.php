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

namespace Unit\View\Lumi\Lexer;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\ByteStream;
use Tuxxedo\View\Lumi\Lexer\LexerException;

class ByteStreamTest extends TestCase
{
    private const string FIXTURE_FILE = __DIR__ . '/../../../../Fixture/View/Lumi/Lexer/ByteStream/sample.txt';

    public function testCreateFromStringStoresInput(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame('hello', $stream->input);
    }

    public function testCreateFromStringNormalizesWindowsLineEndings(): void
    {
        $stream = ByteStream::createFromString("line1\r\nline2");

        self::assertSame("line1\nline2", $stream->input);
    }

    public function testCreateFromStringNormalizesOldMacLineEndings(): void
    {
        $stream = ByteStream::createFromString("line1\rline2");

        self::assertSame("line1\nline2", $stream->input);
    }

    public function testCreateFromStringSetsLength(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame(5, $stream->length);
    }

    public function testCreateFromStringInitialPositionIsZero(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame(0, $stream->position);
    }

    public function testCreateFromStringInitialLineIsOne(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame(1, $stream->line);
    }

    public function testCreateFromFileReadsContents(): void
    {
        $stream = ByteStream::createFromFile(self::FIXTURE_FILE);

        self::assertSame('Hello, World!', $stream->input);
    }

    public function testCreateFromFileThrowsOnMissingFile(): void
    {
        self::expectException(LexerException::class);

        ByteStream::createFromFile('/nonexistent/path/to/missing.txt');
    }

    public function testCloneResetsPositionAndLine(): void
    {
        $stream = ByteStream::createFromString("line1\nline2");

        $stream->consume();
        $stream->consume();

        $cloned = clone $stream;

        self::assertSame(0, $cloned->position);
        self::assertSame(1, $cloned->line);
    }

    public function testEofReturnsFalseInitially(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertFalse($stream->eof());
    }

    public function testEofReturnsTrueOnEmptyInput(): void
    {
        $stream = ByteStream::createFromString('');

        self::assertTrue($stream->eof());
    }

    public function testEofReturnsTrueAfterConsumingAll(): void
    {
        $stream = ByteStream::createFromString('hi');

        $stream->consume();
        $stream->consume();

        self::assertTrue($stream->eof());
    }

    public function testPeekReturnsSingleChar(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame('h', $stream->peek(1));
    }

    public function testPeekReturnsMultipleChars(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame('hel', $stream->peek(3));
    }

    public function testPeekDoesNotAdvancePosition(): void
    {
        $stream = ByteStream::createFromString('hello');

        $stream->peek(3);

        self::assertSame(0, $stream->position);
    }

    public function testPeekWithSkipWhitespaceSkipsLeadingSpaces(): void
    {
        $stream = ByteStream::createFromString('  abc');

        self::assertSame(
            'abc',
            $stream->peek(
                length: 3,
                skipWhitespace: true,
            ),
        );

        self::assertSame(0, $stream->position);
    }

    public function testPeekBeyondEofReturnsAvailableChars(): void
    {
        $stream = ByteStream::createFromString('hi');

        self::assertSame('hi', $stream->peek(10));
    }

    public function testConsumeReturnsChar(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::assertSame('h', $stream->consume());
    }

    public function testConsumeAdvancesPosition(): void
    {
        $stream = ByteStream::createFromString('hello');

        $stream->consume();

        self::assertSame(1, $stream->position);
    }

    public function testConsumeNewlineIncrementsLine(): void
    {
        $stream = ByteStream::createFromString("\nhello");

        $stream->consume();

        self::assertSame(2, $stream->line);
    }

    public function testConsumeThrowsAtEof(): void
    {
        $stream = ByteStream::createFromString('');

        self::expectException(LexerException::class);

        $stream->consume();
    }

    public function testConsumeSequenceAdvancesPositionByLength(): void
    {
        $stream = ByteStream::createFromString('{{ foo }}');

        $stream->consumeSequence('{{');

        self::assertSame(2, $stream->position);
    }

    public function testConsumeSequenceCountsNewlines(): void
    {
        $stream = ByteStream::createFromString("\n\nhello");

        $stream->consumeSequence("\n\n");

        self::assertSame(3, $stream->line);
    }

    public function testConsumeSequenceThrowsOnMismatch(): void
    {
        $stream = ByteStream::createFromString('hello');

        self::expectException(LexerException::class);

        $stream->consumeSequence('{{');
    }

    public function testConsumeWhitespaceSkipsSpaces(): void
    {
        $stream = ByteStream::createFromString('   hello');

        $stream->consumeWhitespace();

        self::assertSame(3, $stream->position);
    }

    public function testConsumeWhitespaceSkipsTabs(): void
    {
        $stream = ByteStream::createFromString("\t\thello");

        $stream->consumeWhitespace();

        self::assertSame(2, $stream->position);
    }

    public function testConsumeWhitespaceSkipsNewlinesAndTracksLines(): void
    {
        $stream = ByteStream::createFromString("\n\nhello");

        $stream->consumeWhitespace();

        self::assertSame(3, $stream->line);
    }

    public function testConsumeWhitespaceReturnsTrueWhenMoreContent(): void
    {
        $stream = ByteStream::createFromString('  hello');

        self::assertTrue($stream->consumeWhitespace());
    }

    public function testConsumeWhitespaceReturnsFalseAtEof(): void
    {
        $stream = ByteStream::createFromString('   ');

        self::assertFalse($stream->consumeWhitespace());
    }

    public function testFindSequenceReturnsOffsetForSimpleMatch(): void
    {
        $stream = ByteStream::createFromString('hello }}');

        self::assertSame(6, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceReturnsNullWhenNotFound(): void
    {
        $stream = ByteStream::createFromString('hello world');

        self::assertNull($stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceIgnoresSequenceInsideDoubleQuotes(): void
    {
        $stream = ByteStream::createFromString('"}} foo" }}');

        self::assertSame(9, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceIgnoresSequenceInsideSingleQuotes(): void
    {
        $stream = ByteStream::createFromString("'}} foo' }}");

        self::assertSame(9, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceHandlesEscapedQuoteInString(): void
    {
        $stream = ByteStream::createFromString('"foo\\"bar" }}');

        self::assertSame(11, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceWithOffset(): void
    {
        $stream = ByteStream::createFromString('}} hello }}');

        self::assertSame(
            9,
            $stream->findSequenceOutsideQuotes(
                sequence: '}}',
                offset: 2,
            ),
        );
    }

    public function testFindSequenceReturnsNullWhenSequenceExceedsRemainingInput(): void
    {
        $stream = ByteStream::createFromString('hi');

        self::assertNull($stream->findSequenceOutsideQuotes('}}}'));
    }

    public function testFindSequenceReturnsNullWhenSequenceIsCutOffAtEnd(): void
    {
        $stream = ByteStream::createFromString('foo }');

        self::assertNull($stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceReturnsNullWhenSequenceOnlyInsideQuotes(): void
    {
        $stream = ByteStream::createFromString('"}} only inside"');

        self::assertNull($stream->findSequenceOutsideQuotes('}}'));
    }

    public function testCreateFromStringSetsLengthInBytes(): void
    {
        $stream = ByteStream::createFromString('æøå');

        self::assertSame(6, $stream->length);
    }

    public function testPeekCharacterReturnsFullMultibyteCharacter(): void
    {
        $stream = ByteStream::createFromString('æb');

        self::assertSame('æ', $stream->peekCharacter());
        self::assertSame(0, $stream->position);
    }

    public function testPeekCharacterReturnsEmptyStringAtEof(): void
    {
        $stream = ByteStream::createFromString('');

        self::assertSame('', $stream->peekCharacter());
    }

    public function testPeekCharacterReturnsTruncatedSequenceAtEnd(): void
    {
        $stream = ByteStream::createFromString("\xE2\x82");

        self::assertSame("\xE2\x82", $stream->peekCharacter());
    }

    public function testConsumeCharacterAdvancesByCharacterBytes(): void
    {
        $stream = ByteStream::createFromString('€b');

        self::assertSame('€', $stream->consumeCharacter());
        self::assertSame(3, $stream->position);
        self::assertSame('b', $stream->consumeCharacter());
        self::assertTrue($stream->eof());
    }

    public function testConsumeCharacterNewlineIncrementsLine(): void
    {
        $stream = ByteStream::createFromString("\nhello");

        $stream->consumeCharacter();

        self::assertSame(2, $stream->line);
    }

    public function testConsumeCharacterThrowsAtEof(): void
    {
        $stream = ByteStream::createFromString('');

        self::expectException(LexerException::class);

        $stream->consumeCharacter();
    }

    public function testConsumeReturnsSingleByteOfMultibyteCharacter(): void
    {
        $stream = ByteStream::createFromString('æ');

        self::assertSame("\xC3", $stream->consume());
        self::assertSame(1, $stream->position);
    }

    public function testConsumeWhitespaceSkipsUnicodeWhitespace(): void
    {
        $stream = ByteStream::createFromString("\u{00A0} \u{2003}\nx");

        $stream->consumeWhitespace();

        self::assertSame(7, $stream->position);
        self::assertSame(2, $stream->line);
    }

    public function testConsumeWhitespaceStopsAtMultibyteNonWhitespace(): void
    {
        $stream = ByteStream::createFromString(' æ');

        $stream->consumeWhitespace();

        self::assertSame(1, $stream->position);
    }

    public function testPeekWithSkipWhitespaceSkipsUnicodeWhitespace(): void
    {
        $stream = ByteStream::createFromString("\u{00A0}abc");

        self::assertSame(
            'ab',
            $stream->peek(
                length: 2,
                skipWhitespace: true,
            ),
        );
    }

    public function testFindSequenceReturnsByteOffsetAfterMultibyteText(): void
    {
        $stream = ByteStream::createFromString('æø }}');

        self::assertSame(5, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceTreatsEscapedQuoteOutsideStringAsText(): void
    {
        $stream = ByteStream::createFromString('\\" }} "x" }}');

        self::assertSame(3, $stream->findSequenceOutsideQuotes('}}'));
    }

    public function testFindSequenceIgnoresOtherQuoteCharacterInsideString(): void
    {
        $stream = ByteStream::createFromString('"it\'s }}" }}');

        self::assertSame(10, $stream->findSequenceOutsideQuotes('}}'));
    }
}
