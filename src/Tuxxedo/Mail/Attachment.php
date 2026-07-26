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

namespace Tuxxedo\Mail;

use Tuxxedo\File\FileException;
use Tuxxedo\File\FileInterface;

class Attachment implements AttachmentInterface
{
    public ?string $name {
        get {
            return $this->file->name;
        }
    }

    public ?string $mimeType {
        get {
            return $this->file->mimeType;
        }
    }

    public ?int $size {
        get {
            return $this->file->size;
        }
    }

    public readonly ?string $contentId;

    /**
     * @throws MailException
     */
    final private function __construct(
        public readonly FileInterface $file,
        public readonly AttachmentDisposition $disposition = AttachmentDisposition::ATTACHMENT,
        ?string $contentId = null,
        public readonly ?string $description = null,
    ) {
        if (
            $description !== null &&
            \preg_match('/[\x00\r\n]/', $description) === 1
        ) {
            throw MailException::fromInvalidDescription();
        }

        if ($contentId !== null) {
            if (\preg_match('/[\x00\r\n]/', $contentId) === 1) {
                throw MailException::fromInvalidContentId();
            }

            $this->contentId = self::wrapAngleBrackets($contentId);
        } elseif ($disposition === AttachmentDisposition::INLINE) {
            $this->contentId = self::generateContentId();
        } else {
            $this->contentId = null;
        }
    }

    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public static function attachment(
        FileInterface $file,
        ?string $description = null,
    ): self {
        return new self(
            file: $file,
            disposition: AttachmentDisposition::ATTACHMENT,
            description: $description,
        );
    }

    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public static function inline(
        FileInterface $file,
        ?string $contentId = null,
        ?string $description = null,
    ): self {
        return new self(
            file: $file,
            disposition: AttachmentDisposition::INLINE,
            contentId: $contentId,
            description: $description,
        );
    }

    /**
     * @throws FileException
     */
    #[\NoDiscard]
    public function contents(): string
    {
        return $this->file->contents();
    }

    private static function wrapAngleBrackets(
        string $value,
    ): string {
        return '<' . \trim($value, '<>') . '>';
    }

    private static function generateContentId(): string
    {
        return \sprintf(
            '<%s@%s>',
            \bin2hex(\random_bytes(16)),
            \bin2hex(\random_bytes(8)),
        );
    }
}
