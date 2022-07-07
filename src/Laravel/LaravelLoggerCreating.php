<?php

declare(strict_types=1);

namespace Gotphoto\Logging\Laravel;

use Gotphoto\Logging\Formatter;
use Illuminate\Support\Facades\App;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NewRelic\Monolog\Enricher\Processor;

final class LaravelLoggerCreating
{
    public function __invoke(array $config)
    {
        assert(!empty($config['app_name']) && is_string($config['app_name']));
        assert(!empty($config['channel']) && is_string($config['channel']));
        $appName = $config['app_name'];
        $channel = $config['channel'];

        $log = new Logger($channel);
        $log->pushProcessor(new Processor());

        $streamHandler = new StreamHandler('php://stderr');

        $handler = $streamHandler;
        $env     = App::environment();
        $handler->setFormatter(
            new Formatter($appName, (is_string($env) ? $env : "undefined"), [])
        );
        $log->pushHandler($handler);

        return $log;
    }
}
