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

namespace Tuxxedo\Mail\Serializer;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\HeaderInterface;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\Mime\MimePart;
use Tuxxedo\Mail\Serializer\Mime\MimePartInterface;
use Tuxxedo\Mail\Serializer\Mime\MultipartMimePart;
use Tuxxedo\Mail\Serializer\Render\EncodedWord;

class HeaderBlockBuilder
{
    private const int FOLD_WIDTH = 76;

    public static function build(
        MessageInterface $message,
        MimePartInterface $rootPart,
    ): string {
        $overrides = self::indexOverrides($message->extraHeaders);
        $lines = [
            'From: ' . $message->from->toRfc5322(),
        ];

        if ($message->sender !== null) {
            $lines[] = 'Sender: ' . $message->sender->toRfc5322();
        }

        if ($message->replyTo !== []) {
            $lines[] = 'Reply-To: ' . self::renderAddressList($message->replyTo);
        }

        if ($message->returnPath !== null) {
            $lines[] = 'Return-Path: ' . $message->returnPath->toRfc5322();
        }

        if ($message->to !== []) {
            $lines[] = 'To: ' . self::renderAddressList($message->to);
        }

        if ($message->cc !== []) {
            $lines[] = 'Cc: ' . self::renderAddressList($message->cc);
        }

        $lines[] = 'Subject: ' . EncodedWord::encodeIfNonAscii($message->subject);
        $lines[] = 'Date: ' . $message->date->format(\DateTimeInterface::RFC2822);
        $lines[] = 'Message-ID: ' . $message->messageId;
        $lines[] = 'MIME-Version: 1.0';

        $lines[] = isset($overrides['content-type'])
            ? $overrides['content-type']->toRfc5322()
            : self::renderContentType($rootPart);

        if ($rootPart instanceof MimePart) {
            $lines[] = isset($overrides['content-transfer-encoding'])
                ? $overrides['content-transfer-encoding']->toRfc5322()
                : 'Content-Transfer-Encoding: ' . $rootPart->encoding->value;
        }

        foreach ($message->extraHeaders as $header) {
            $lower = \strtolower($header->name);

            if ($lower === 'content-type' || $lower === 'content-transfer-encoding') {
                continue;
            }

            $lines[] = $header->toRfc5322();
        }

        return \implode(
            "\r\n",
            \array_map(
                static fn (string $line): string => self::fold($line),
                $lines,
            ),
        );
    }

    /**
     * @param list<HeaderInterface> $extraHeaders
     * @return array<string, HeaderInterface>
     */
    private static function indexOverrides(
        array $extraHeaders,
    ): array {
        $result = [];

        foreach ($extraHeaders as $header) {
            $result[\strtolower($header->name)] = $header;
        }

        return $result;
    }

    public static function renderContentType(
        MimePartInterface $part,
    ): string {
        if ($part instanceof MultipartMimePart) {
            return \sprintf(
                'Content-Type: %s; boundary="%s"',
                $part->mimeType,
                $part->boundary,
            );
        }

        if (\str_starts_with($part->mimeType, 'text/')) {
            return \sprintf(
                'Content-Type: %s; charset=UTF-8',
                $part->mimeType,
            );
        }

        return 'Content-Type: ' . $part->mimeType;
    }

    public static function fold(
        string $line,
    ): string {
        return \wordwrap($line, self::FOLD_WIDTH, "\r\n\t", false);
    }

    /**
     * @param list<AddressInterface> $addresses
     */
    private static function renderAddressList(
        array $addresses,
    ): string {
        return \implode(
            ', ',
            \array_map(
                static fn (AddressInterface $address): string => $address->toRfc5322(),
                $addresses,
            ),
        );
    }
}
