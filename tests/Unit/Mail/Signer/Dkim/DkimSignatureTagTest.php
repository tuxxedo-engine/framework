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

namespace Unit\Mail\Signer\Dkim;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Signer\Dkim\DkimAlgorithm;
use Tuxxedo\Mail\Signer\Dkim\DkimCanonicalization;
use Tuxxedo\Mail\Signer\Dkim\DkimSignatureTag;

class DkimSignatureTagTest extends TestCase
{
    public function testHeaderValueOmitsTimestampWhenNull(): void
    {
        $tag = new DkimSignatureTag(
            algorithm: DkimAlgorithm::RSA_SHA256,
            headerCanonicalization: DkimCanonicalization::RELAXED,
            bodyCanonicalization: DkimCanonicalization::RELAXED,
            domain: 'example.com',
            selector: 'default',
            signedHeaders: [
                'From',
                'Subject',
            ],
            bh: 'body-hash',
            b: 'signature',
            timestamp: null,
        );

        self::assertSame(
            'v=1; a=rsa-sha256; c=relaxed/relaxed; d=example.com; s=default; h=From:Subject; bh=body-hash; b=signature',
            $tag->toHeaderValue(),
        );
    }
}
