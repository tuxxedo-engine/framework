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

use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\Encoding\Encoder;
use Tuxxedo\Mail\Serializer\Mime\MimePart;
use Tuxxedo\Mail\Serializer\Mime\MimePartInterface;
use Tuxxedo\Mail\Serializer\Mime\MultipartMimePart;

class MessageSerializer implements MessageSerializerInterface
{
    public function serialize(
        MessageInterface $message,
    ): SerializedMessageInterface {
        $rootPart = MimeTreeBuilder::build($message);
        $headers = HeaderBlockBuilder::build($message, $rootPart);
        $body = self::renderRootBody($rootPart);

        return new SerializedMessage(
            source: $message,
            headers: $headers,
            body: $body,
        );
    }

    private static function renderRootBody(
        MimePartInterface $part,
    ): string {
        if ($part instanceof MimePart) {
            return Encoder::encode($part->body, $part->encoding);
        }

        if ($part instanceof MultipartMimePart) {
            return self::renderMultipartBody($part);
        }

        // @codeCoverageIgnoreStart
        throw MailException::fromNonSerializableMimePart(
            mimePartClass: $part::class,
        );
        // @codeCoverageIgnoreEnd
    }

    private static function renderMultipartBody(
        MultipartMimePart $part,
    ): string {
        $out = '';

        foreach ($part->children as $child) {
            $out .= '--' . $part->boundary . "\r\n";
            $out .= \rtrim(self::renderChild($child), "\r\n") . "\r\n";
        }

        $out .= '--' . $part->boundary . "--\r\n";

        return $out;
    }

    private static function renderChild(
        MimePartInterface $part,
    ): string {
        $headers = self::renderChildHeaders($part);

        if ($part instanceof MimePart) {
            return $headers . "\r\n\r\n" . Encoder::encode($part->body, $part->encoding);
        }

        if ($part instanceof MultipartMimePart) {
            return $headers . "\r\n\r\n" . self::renderMultipartBody($part);
        }

        // @codeCoverageIgnoreStart
        throw MailException::fromNonSerializableMimePart(
            mimePartClass: $part::class,
        );
        // @codeCoverageIgnoreEnd
    }

    private static function renderChildHeaders(
        MimePartInterface $part,
    ): string {
        $lines = [
            HeaderBlockBuilder::renderContentType($part),
        ];

        if ($part instanceof MimePart) {
            $lines[] = 'Content-Transfer-Encoding: ' . $part->encoding->value;
        }

        foreach ($part->headers as $header) {
            $lines[] = $header->toRfc5322();
        }

        return \join(
            "\r\n",
            \array_map(
                static fn (string $line): string => HeaderBlockBuilder::fold($line),
                $lines,
            ),
        );
    }
}
