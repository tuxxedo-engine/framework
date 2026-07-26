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

use Tuxxedo\File\FileInterface;

interface AttachmentInterface extends FileInterface
{
    public FileInterface $file {
        get;
    }

    public AttachmentDisposition $disposition {
        get;
    }

    public ?string $contentId {
        get;
    }

    public ?string $description {
        get;
    }
}
