<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Request\Middleware;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class XssProtection implements MiddlewareInterface
{
    /**
     * @param array<string, string> $contentSecurityPolicies
     */
    public function __construct(
        public array $contentSecurityPolicies = [
            'default-src' => '\'self\'',
        ],
        public readonly bool $xssProtectionEnabled = true,
        public readonly bool $xssProtectionBlock = true,
        public readonly ?string $xssProtectionReportUri = null,
        public readonly bool $contentTypeOptionsNoSniff = true,
    ) {
    }

    private function getContentSecurityPolicyValue(): string
    {
        $values = [];

        foreach ($this->contentSecurityPolicies as $directive => $value) {
            $values[] = $directive . ' ' . $value;
        }

        return \join('; ', $values);
    }

    private function getXssProtectionValue(): string
    {
        if (!$this->xssProtectionEnabled) {
            return '0';
        }

        if ($this->xssProtectionReportUri !== null) {
            return '1; report=' . $this->xssProtectionReportUri;
        }

        if ($this->xssProtectionBlock) {
            return '1; mode=block';
        }

        return '1';
    }

    /**
     * @return HeaderInterface[]
     */
    private function getHeaders(): array
    {
        $headers = [];

        if (\sizeof($this->contentSecurityPolicies) > 0) {
            $headers[] = new Header('Content-Security-Policy', $this->getContentSecurityPolicyValue());
        }

        $headers[] = new Header('X-XSS-Protection', $this->getXssProtectionValue());

        if ($this->contentTypeOptionsNoSniff) {
            $headers[] = new Header('X-Content-Type-Options', 'nosniff');
        }

        return $headers;
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        return $next->handle($request, $next)
            ->withHeaders(
                $this->getHeaders(),
            );
    }
}
