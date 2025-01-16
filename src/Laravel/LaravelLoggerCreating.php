<?php

declare(strict_types=1);

namespace Gotphoto\Logging\Laravel;

use Aws\Exception\AwsException;
use Gotphoto\Logging\ExceptionContext\AwsExceptionContext;
use Gotphoto\Logging\ExceptionContext\GuzzleRequestExceptionContext;
use Gotphoto\Logging\LogstashFormatter;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\App;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use Psr\Log\LogLevel;

final class LaravelLoggerCreating
{
    public function __invoke(array $config)
    {
        assert(!empty($config['app_name']) && is_string($config['app_name']));
        assert(!empty($config['channel']) && is_string($config['channel']));
        assert(!isset($config['processors']) || is_array($config['processors']));
        $appName = $config['app_name'];
        $channel = $config['channel'];
        /** @var ProcessorInterface[] $processors */
        $processors = $config['processors'] ?? [];
        /** @var array<string, array<array-key, callable>> $exceptionContexts */
        $exceptionContexts = $config['exceptionContexts'] ?? [];
        /** @var int $level */
        $level = $config['level'] ?? Level::Debug;
        /** @var string $stream */
        $stream = $config['stream_to'] ?? 'php://stderr';

        $log = new Logger($channel);
        $log->pushProcessor(new PsrLogMessageProcessor());

        foreach ($processors as $processor) {
            $log->pushProcessor($processor);
        }

        $streamHandler = new StreamHandler($stream, $level);

        $handler = $streamHandler;
        $env = App::environment();
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $handler->setFormatter(
            new LogstashFormatter($appName, (is_string($env) ? $env : "undefined"), array_merge($exceptionContexts, [
                RequestException::class => [new GuzzleRequestExceptionContext()],
                AwsException::class => [new AwsExceptionContext()],
            ])),
        );
        $log->pushHandler($handler);

        $otelHandler = new Handler(
            Globals::loggerProvider(),
            LogLevel::INFO,
        );
        /** @psalm-suppress ArgumentTypeCoercion */
        $otelHandler->setFormatter(
            new \Gotphoto\Logging\OtelFormatter([
                RequestException::class => [new GuzzleRequestExceptionContext()],
                AwsException::class => [new AwsExceptionContext()],
            ]),
        );
        $log->pushHandler($otelHandler);

        return $log;
    }
}
