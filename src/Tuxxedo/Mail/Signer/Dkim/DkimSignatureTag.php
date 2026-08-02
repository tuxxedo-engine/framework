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

namespace Tuxxedo\Mail\Signer\Dkim;

class DkimSignatureTag
{
    /**
     * @param list<string> $signedHeaders
     */
    public function __construct(
        public readonly DkimAlgorithm $algorithm,
        public readonly DkimCanonicalization $headerCanonicalization,
        public readonly DkimCanonicalization $bodyCanonicalization,
        public readonly string $domain,
        public readonly string $selector,
        public readonly array $signedHeaders,
        public readonly string $bh,
        public readonly string $b,
        public readonly ?int $timestamp = null,
    ) {
    }

    public function withB(
        string $b,
    ): self {
        return new self(
            algorithm: $this->algorithm,
            headerCanonicalization: $this->headerCanonicalization,
            bodyCanonicalization: $this->bodyCanonicalization,
            domain: $this->domain,
            selector: $this->selector,
            signedHeaders: $this->signedHeaders,
            bh: $this->bh,
            b: $b,
            timestamp: $this->timestamp,
        );
    }

    public function toHeaderValue(): string
    {
        $parts = [
            'v=1',
            'a=' . $this->algorithm->value,
            'c=' . $this->headerCanonicalization->value . '/' . $this->bodyCanonicalization->value,
            'd=' . $this->domain,
            's=' . $this->selector,
            'h=' . \implode(':', $this->signedHeaders),
            'bh=' . $this->bh,
        ];

        if ($this->timestamp !== null) {
            $parts[] = 't=' . $this->timestamp;
        }

        $parts[] = 'b=' . $this->b;

        return \implode('; ', $parts);
    }
}
