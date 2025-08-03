<?php

declare(strict_types=1);

use App\Services\Logger\Logger;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Application\Profile;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Discovery\Bridges\ComposerDiscoverer;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\StrictTransportSecurityMiddleware;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Session\Session;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Kernel::createFromDirectory(
    directory: __DIR__ . '/../app',
);

$app->discover(
    channel: new ComposerDiscoverer(),
);

if ($app->appProfile === Profile::DEBUG) {
    $app->defaultExceptionHandler(
        static fn (): ErrorHandlerInterface => new DebugErrorHandler(
            registerPhpErrorHandler: true,
        ),
    );
} else {
    $app->defaultExceptionHandler(
        new class ($app->container) implements ErrorHandlerInterface {
            public function __construct(
                private readonly ContainerInterface $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $html = '';
                $html .= '<h2>Exception</h2>';
                $html .= '<pre>';
                $html .= $exception;
                $html .= '</pre>';
                $html .= '<h2>Logger</h2>';

                $logger = $this->container->resolve(LoggerInterface::class);

                if (\sizeof($logger) > 0) {
                    $html .= '<pre>';
                    $html .= $logger->formatEntries();
                    $html .= '</pre>';
                } else {
                    $html .= '<em>No log entries</em>';
                }

                return $response->withBody($html);
            }
        },
    );
}

$app->container->bind(Logger::class);
$app->container->bind(Session::class);

$app->router(
    DynamicRouter::createFromDirectory(
        container: $app->container,
        directory: __DIR__ . '/../app/Controllers',
        baseNamespace: '\App\Controllers\\',
        strictMode: true,
    ),
);

$app->middleware(
    static fn (): MiddlewareInterface => new StrictTransportSecurityMiddleware(),
);

$app->run();
