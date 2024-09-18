<?php

declare(strict_types=1);

use Gotphoto\Logging\ExceptionContext\AwsExceptionContext;
use Gotphoto\Logging\ExceptionContext\GuzzleRequestExceptionContext;
use Gotphoto\Logging\Formatter;
use Gotphoto\Logging\NewrelicProcessor;
use Gotphoto\Logging\OtelFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator) {
    $s = $containerConfigurator->services();

    $s->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $s->set(NewrelicProcessor::class)
        ->tag('monolog.processor');
    $s->set(PsrLogMessageProcessor::class)
        ->tag('monolog.processor');

    $s->set(Formatter::class);

    $s->set(AwsExceptionContext::class)
        ->tag('gotphoto_logging.exception_context');
    $s->set(GuzzleRequestExceptionContext::class)
        ->tag('gotphoto_logging.exception_context');

    $s->set(OtelFormatter::class);
    $s->set(Handler::class)
        ->arg(
            '$loggerProvider',
            inline_service(LoggerProviderInterface::class)
                ->factory([Globals::class, 'loggerProvider']),
        )
        ->arg('$level', LogLevel::INFO)
        ->call('setFormatter', [service(OtelFormatter::class)]);
};
