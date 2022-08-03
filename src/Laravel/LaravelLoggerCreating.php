<?php

declare(strict_types=1);

namespace Gotphoto\Logging\Laravel;

use Aws\Exception\AwsException;
use Gotphoto\Logging\ExceptionContext\AwsExceptionContext;
use Gotphoto\Logging\ExceptionContext\GuzzleRequestExceptionContext;
use Gotphoto\Logging\Formatter;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use NewRelic\Monolog\Enricher\Processor;

final class LaravelLoggerCreating
{
    public function __invoke(array $config)
    {
        assert(!empty($config['app_name']) && is_string($config['app_name']));
        assert(!empty($config['channel']) && is_string($config['channel']));
        assert(!isset($config['processors']) || is_array($config['processors']));
        $appName = $config['app_name'];
        $channel = $config['channel'];
        /** @var \Monolog\Processor\ProcessorInterface[] $processors */
        $processors = $config['processors'] ?? [];
        /** @var int $level */
        $level = $config['level'] ?? Logger::DEBUG;

        $log = new Logger($channel);
        $log->pushProcessor(new Processor());
        $log->pushProcessor(new PsrLogMessageProcessor());

        foreach ($processors as $processor) {
            $log->pushProcessor($processor);
        }

        $streamHandler = new StreamHandler('php://stderr', $level);

        $handler = $streamHandler;
        $env     = App::environment();
        $handler->setFormatter(
            new Formatter($appName, (is_string($env) ? $env : "undefined"), [
                RequestException::class => [new GuzzleRequestExceptionContext()],
                AwsException::class     => [new AwsExceptionContext()],
            ])
        );
        $log->pushHandler($handler);

        return $log;
    }
}
