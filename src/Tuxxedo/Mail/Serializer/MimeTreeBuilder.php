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

use Tuxxedo\File\FileException;
use Tuxxedo\Mail\AttachmentDisposition;
use Tuxxedo\Mail\AttachmentInterface;
use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\Encoding\ContentTransferEncodingSelector;
use Tuxxedo\Mail\Serializer\Mime\ContentTransferEncoding;
use Tuxxedo\Mail\Serializer\Mime\MimePart;
use Tuxxedo\Mail\Serializer\Mime\MimePartInterface;
use Tuxxedo\Mail\Serializer\Mime\MultipartMimePart;
use Tuxxedo\Mail\Serializer\Mime\MultipartSubtype;

class MimeTreeBuilder
{
    /**
     * @throws MailException
     */
    public static function build(
        MessageInterface $message,
    ): MimePartInterface {
        $bodyTree = self::buildBodyTree($message);
        $regularAttachments = self::filterAttachments($message->attachments, AttachmentDisposition::ATTACHMENT);

        if ($regularAttachments === []) {
            return $bodyTree;
        }

        $children = [
            $bodyTree,
        ];

        foreach ($regularAttachments as $attachment) {
            $children[] = self::attachmentPart($attachment);
        }

        return new MultipartMimePart(
            subtype: MultipartSubtype::MIXED,
            boundary: self::generateBoundary(),
            children: $children,
        );
    }

    /**
     * @throws MailException
     */
    private static function buildBodyTree(
        MessageInterface $message,
    ): MimePartInterface {
        $bodyContent = $message->body ?? '';
        $bodyMime = $message->bodyType->value;

        $bodyPart = new MimePart(
            mimeType: $bodyMime,
            body: $bodyContent,
            encoding: ContentTransferEncodingSelector::selectFor($bodyContent, $bodyMime),
        );

        $inlineAttachments = self::filterAttachments($message->attachments, AttachmentDisposition::INLINE);

        $htmlSide = $inlineAttachments !== []
            ? self::wrapInRelated($bodyPart, $inlineAttachments)
            : $bodyPart;

        if ($message->alternativeText === null) {
            return $htmlSide;
        }

        $textPart = new MimePart(
            mimeType: 'text/plain',
            body: $message->alternativeText,
            encoding: ContentTransferEncodingSelector::selectFor($message->alternativeText, 'text/plain'),
        );

        return new MultipartMimePart(
            subtype: MultipartSubtype::ALTERNATIVE,
            boundary: self::generateBoundary(),
            children: [
                $textPart,
                $htmlSide,
            ],
        );
    }

    /**
     * @param list<AttachmentInterface> $inlines
     *
     * @throws MailException
     */
    private static function wrapInRelated(
        MimePartInterface $primary,
        array $inlines,
    ): MultipartMimePart {
        $children = [
            $primary,
        ];

        foreach ($inlines as $inline) {
            $children[] = self::attachmentPart($inline);
        }

        return new MultipartMimePart(
            subtype: MultipartSubtype::RELATED,
            boundary: self::generateBoundary(),
            children: $children,
        );
    }

    /**
     * @throws MailException
     */
    private static function attachmentPart(
        AttachmentInterface $attachment,
    ): MimePartInterface {
        try {
            $bytes = $attachment->contents();
        } catch (FileException $e) {
            throw MailException::fromAttachmentReadFailure(
                filename: $attachment->name,
                previous: $e,
            );
        }

        $mimeType = $attachment->mimeType ?? 'application/octet-stream';
        $headers = [
            self::buildContentDispositionHeader($attachment),
        ];

        if ($attachment->contentId !== null) {
            $headers[] = new Header('Content-ID', $attachment->contentId);
        }

        if ($attachment->description !== null) {
            $headers[] = new Header('Content-Description', $attachment->description);
        }

        return new MimePart(
            mimeType: $mimeType,
            body: $bytes,
            encoding: ContentTransferEncoding::BASE64,
            headers: $headers,
        );
    }

    /**
     * @throws MailException
     */
    private static function buildContentDispositionHeader(
        AttachmentInterface $attachment,
    ): Header {
        $value = $attachment->disposition->value;

        if ($attachment->name !== null) {
            $value .= \sprintf(
                '; filename="%s"',
                \str_replace(
                    [
                        '\\',
                        '"',
                    ],
                    [
                        '\\\\',
                        '\\"',
                    ],
                    $attachment->name,
                ),
            );
        }

        return new Header('Content-Disposition', $value);
    }

    private static function generateBoundary(): string
    {
        return \bin2hex(\random_bytes(16));
    }

    /**
     * @param list<AttachmentInterface> $attachments
     * @return list<AttachmentInterface>
     */
    private static function filterAttachments(
        array $attachments,
        AttachmentDisposition $disposition,
    ): array {
        $result = [];

        foreach ($attachments as $attachment) {
            if ($attachment->disposition === $disposition) {
                $result[] = $attachment;
            }
        }

        return $result;
    }
}
