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

namespace Tuxxedo\Debug;

use Tuxxedo\Application\Config\AppConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Debug\Config\DebugConfigInterface;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Version;

class DebugErrorHandler implements ErrorHandlerInterface
{
    private static bool $registeredPhpErrorHandler = false;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly DebugConfigInterface $config,
    ) {
        if ($this->config->registerPhpErrorHandler) {
            self::registerPhpErrorHandler();
        }
    }

    public function __destruct()
    {
        if (self::$registeredPhpErrorHandler) {
            self::restorePhpErrorHandler();
        }
    }

    public static function registerPhpErrorHandler(): void
    {
        \set_error_handler(
            static fn (int $errno, string $errstr, ?string $errfile, ?int $errline): never => throw new \ErrorException(
                message: $errstr,
                severity: $errno,
                filename: $errfile ?? '',
                line: $errline ?? 0,
            ),
        );

        self::$registeredPhpErrorHandler = true;
    }

    public static function restorePhpErrorHandler(): void
    {
        \restore_error_handler();

        self::$registeredPhpErrorHandler = false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function handle(
        RequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception,
    ): ResponseInterface {
        if (!$this->config->alwaysShow && $response->body !== '') {
            return $response;
        }

        \ob_clean();

        $appConfig = $this->container->resolve(AppConfigInterface::class);
        $timestamp = new \DateTimeImmutable();
        $timestampFormatted = $timestamp->format('Y-m-d H:i:s T') . ' (UTC' . $timestamp->format('P') . ')';

        $fqn = $exception::class;
        $location = self::formatLocation($exception->getFile(), $exception->getLine(), $this->config->rootPath);
        $code = $exception->getCode();

        $html = '';
        $html .= '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>Tuxxedo Engine Debugger</title>';
        $html .= '<style>';
        $html .= ':root { --bg: #1a1a1e; --panel: rgba(24, 24, 30, 0.72); --fg: #ececec; --dim: #b2afac; --line: #33363f; --hover: #2f3441; --path: #a3c8de; --sans: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; --mono: "JetBrains Mono", "JetBrainsMono Nerd Font", Consolas, Menlo, "Liberation Mono", monospace; }';
        $html .= '* { box-sizing: border-box; }';
        $html .= 'html, body { overflow-x: hidden; }';
        $html .= 'body { margin: 0; background: var(--bg); color: var(--fg); font: 14px/1.55 var(--sans); -webkit-font-smoothing: antialiased; position: relative; }';
        $html .= 'code { font-family: var(--mono); font-size: 0.9em; background: rgba(0, 0, 0, 0.32); padding: 1px 6px; border-radius: 4px; }';
        $html .= '.backdrop { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }';
        $html .= '.blob { position: absolute; border-radius: 50%; filter: blur(100px); will-change: transform; }';
        $html .= '.b1 { top: -20%; left: -12%; width: 55vw; height: 55vw; background: #7c3aed; opacity: 0.32; animation: drift1 34s ease-in-out infinite alternate; }';
        $html .= '.b2 { bottom: -25%; right: -8%; width: 60vw; height: 60vw; background: #ec4899; opacity: 0.26; animation: drift2 42s ease-in-out infinite alternate; }';
        $html .= '.b3 { top: 30%; left: 30%; width: 45vw; height: 45vw; background: #3b82f6; opacity: 0.20; animation: drift3 48s ease-in-out infinite alternate; }';
        $html .= '@keyframes drift1 { from { transform: translate(0, 0) scale(1); } to { transform: translate(22vw, 18vh) scale(1.2); } }';
        $html .= '@keyframes drift2 { from { transform: translate(0, 0) scale(1); } to { transform: translate(-22vw, -14vh) scale(1.15); } }';
        $html .= '@keyframes drift3 { from { transform: translate(0, 0) scale(1); } to { transform: translate(-18vw, 22vh) scale(1.3); } }';
        $html .= '@media (prefers-reduced-motion: reduce) { .blob { animation: none; } }';
        $html .= 'main { max-width: 1100px; margin: 40px auto; padding: 40px 40px 56px; position: relative; z-index: 1; background: var(--panel); backdrop-filter: blur(24px) saturate(120%); -webkit-backdrop-filter: blur(24px) saturate(120%); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 8px; }';
        $html .= 'header { display: flex; align-items: baseline; justify-content: space-between; gap: 24px; margin-bottom: 32px; }';
        $html .= 'header h1 { margin: 0; font: 700 26px/1.15 var(--sans); letter-spacing: -0.01em; }';
        $html .= 'header h1 sup { font-family: var(--mono); font-size: 11px; font-weight: 500; color: var(--dim); margin-left: 6px; top: -1.05em; letter-spacing: 0; }';
        $html .= 'header .meta { color: var(--dim); font-size: 13px; text-align: right; }';
        $html .= 'header .meta div + div { margin-top: 2px; }';
        $html .= 'section.exception { margin-bottom: 44px; }';
        $html .= 'section.exception .class { font-family: var(--mono); font-size: 18px; font-weight: 700; margin: 0; word-break: break-word; }';
        $html .= 'section.exception .loc { margin: 4px 0 0 0; color: var(--dim); font-size: 13px; font-family: var(--mono); }';
        $html .= 'section.exception .loc .path { color: var(--path); }';
        $html .= 'section.exception .loc .sep { display: inline-block; width: 20px; }';
        $html .= 'section.exception .message { margin: 20px 0 0 0; color: var(--fg); font-size: 16px; line-height: 1.5; max-width: 68ch; }';
        $html .= 'section.exception .previous { margin-top: 24px; }';
        $html .= 'section.exception .previous .caption, section.trace .caption { margin: 0 0 12px 0; font-size: 12px; color: var(--dim); }';
        $html .= 'section.exception .previous ul { list-style: none; margin: 0; padding: 0; border-top: 1px solid var(--line); }';
        $html .= 'section.exception .previous li { display: grid; grid-template-columns: 44px 1fr; column-gap: 14px; padding: 9px 10px; border-bottom: 1px solid var(--line); cursor: pointer; }';
        $html .= 'section.exception .previous li:hover { background: var(--hover); }';
        $html .= 'section.exception .previous li::before { content: "\21B3"; color: var(--dim); font-size: 13px; padding-top: 1px; font-family: var(--mono); }';
        $html .= 'section.exception .previous .call { font-family: var(--mono); font-size: 13px; word-break: break-word; }';
        $html .= 'section.exception .previous .file { margin-top: 3px; font-size: 12px; color: var(--path); font-family: var(--mono); }';
        $html .= 'section.trace { margin-top: 8px; }';
        $html .= 'section.trace ol { list-style: none; margin: 0; padding: 0; counter-reset: frame -1; border-top: 1px solid var(--line); }';
        $html .= 'section.trace li { counter-increment: frame; display: grid; grid-template-columns: 44px 1fr; column-gap: 14px; padding: 9px 10px; border-bottom: 1px solid var(--line); cursor: pointer; }';
        $html .= 'section.trace li:hover { background: var(--hover); }';
        $html .= 'section.trace li::before { content: "#" counter(frame); color: var(--dim); font-size: 12px; padding-top: 1px; font-family: var(--mono); }';
        $html .= 'section.trace .call { font-family: var(--mono); font-size: 13px; word-break: break-word; }';
        $html .= 'section.trace .file { margin-top: 3px; font-size: 12px; color: var(--path); font-family: var(--mono); }';
        $html .= 'main a { color: inherit; text-decoration: none; }';
        $html .= 'main a:hover { text-decoration: underline; }';
        $html .= 'section.trace a, section.exception .previous a { display: block; }';
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="backdrop" aria-hidden="true"><div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div></div>';
        $html .= '<main>';
        $html .= '<header>';
        $html .= '<h1>Tuxxedo Engine Debugger<sup>' . \htmlspecialchars(Version::SIMPLE) . '</sup></h1>';
        $html .= '<div class="meta"><div>' . \htmlspecialchars($appConfig->name . ' ' . $appConfig->version) . '</div><div>' . \htmlspecialchars($timestampFormatted) . '</div></div>';
        $html .= '</header>';
        $exceptionEditorUrl = $this->formatEditorUrl($exception->getFile(), $exception->getLine());
        $pathSpan = '<span class="path">' . \htmlspecialchars($location) . '</span>';

        if ($exceptionEditorUrl !== null) {
            $pathSpan = '<a href="' . \htmlspecialchars($exceptionEditorUrl) . '">' . $pathSpan . '</a>';
        }

        $html .= '<section class="exception">';
        $html .= '<p class="class">' . \htmlspecialchars($fqn) . '</p>';
        $html .= '<p class="loc">' . $pathSpan . '<span class="sep"></span>code ' . \htmlspecialchars(\strval($code)) . '</p>';
        $html .= '<p class="message">' . \nl2br(\htmlspecialchars($exception->getMessage())) . '</p>';

        $previous = $exception->getPrevious();

        if ($previous !== null) {
            $html .= '<div class="previous">';
            $html .= '<p class="caption">Caused by</p>';
            $html .= '<ul>';

            while ($previous !== null) {
                $prevFqn = $previous::class;
                $lastBackslash = \strrpos($prevFqn, '\\');
                $prevShortName = $lastBackslash === false
                    ? $prevFqn
                    : \substr($prevFqn, $lastBackslash + 1);

                $prevLocation = self::formatLocation($previous->getFile(), $previous->getLine(), $this->config->rootPath);
                $prevEditorUrl = $this->formatEditorUrl($previous->getFile(), $previous->getLine());
                $prevInner = '<div class="call">' . \htmlspecialchars($prevShortName . ': ' . $previous->getMessage()) . '</div><div class="file">' . \htmlspecialchars($prevLocation) . '</div>';

                if ($prevEditorUrl !== null) {
                    $html .= '<li><a href="' . \htmlspecialchars($prevEditorUrl) . '">' . $prevInner . '</a></li>';
                } else {
                    $html .= '<li><div>' . $prevInner . '</div></li>';
                }

                $previous = $previous->getPrevious();
            }

            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</section>';
        $html .= '<section class="trace">';
        $html .= '<p class="caption">Stack trace</p>';
        $html .= '<ol>';

        foreach ($exception->getTrace() as $frame) {
            $call = self::formatFrameCall($frame);
            $file = isset($frame['file'])
                ? self::trimPath($frame['file'], $this->config->rootPath)
                : '[internal function]';

            $line = isset($frame['line'])
                ? ':' . \strval($frame['line'])
                : '';

            $frameEditorUrl = isset($frame['file'], $frame['line'])
                ? $this->formatEditorUrl($frame['file'], $frame['line'])
                : null;

            $frameInner = '<div class="call">' . \htmlspecialchars($call) . '</div><div class="file">' . \htmlspecialchars($file . $line) . '</div>';

            if ($frameEditorUrl !== null) {
                $html .= '<li><a href="' . \htmlspecialchars($frameEditorUrl) . '">' . $frameInner . '</a></li>';
            } else {
                $html .= '<li><div>' . $frameInner . '</div></li>';
            }
        }

        $html .= '</ol>';
        $html .= '</section>';
        $html .= '</main>';
        $html .= '</body>';
        $html .= '</html>';

        return $response->withBody($html);
    }

    /**
     * @codeCoverageIgnore
     */
    private function formatEditorUrl(
        string $file,
        int $line,
    ): ?string {
        if ($this->config->openInEditor === null || $file === '') {
            return null;
        }

        $normalized = \str_replace('\\', '/', $file);

        if ($this->config->editorRemotePath !== null && $this->config->editorLocalPath !== null) {
            $remote = \str_replace('\\', '/', $this->config->editorRemotePath);
            $local = \str_replace('\\', '/', $this->config->editorLocalPath);

            if (\str_starts_with($normalized, $remote)) {
                $normalized = $local . \substr($normalized, \strlen($remote));
            }
        }

        return $this->config->openInEditor->formatUrl($normalized, $line);
    }

    /**
     * @codeCoverageIgnore
     */
    private static function formatLocation(
        string $file,
        int $line,
        string $rootPath,
    ): string {
        if ($file === '') {
            return 'unknown:' . \strval($line);
        }

        return self::trimPath($file, $rootPath) . ':' . \strval($line);
    }

    /**
     * @codeCoverageIgnore
     */
    private static function trimPath(
        string $file,
        string $rootPath,
    ): string {
        $normalized = \str_replace('\\', '/', $file);
        $normalizedRoot = \str_replace('\\', '/', $rootPath);

        if (\str_starts_with($normalized, $normalizedRoot)) {
            return \ltrim(\substr($normalized, \strlen($normalizedRoot)), '/');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $frame
     *
     * @codeCoverageIgnore
     */
    private static function formatFrameCall(
        array $frame,
    ): string {
        $function = isset($frame['function']) && \is_string($frame['function'])
            ? $frame['function']
            : '';

        if ($function === '') {
            return '{main}';
        }

        $class = isset($frame['class']) && \is_string($frame['class'])
            ? $frame['class']
            : null;

        if ($class !== null) {
            $type = isset($frame['type']) && \is_string($frame['type'])
                ? $frame['type']
                : '::';

            return $class . $type . $function . '()';
        }

        return $function . '()';
    }
}
