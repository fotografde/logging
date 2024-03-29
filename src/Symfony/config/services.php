<?php

declare(strict_types=1);

use Gotphoto\Logging\ExceptionContext\AwsExceptionContext;
use Gotphoto\Logging\ExceptionContext\GuzzleRequestExceptionContext;
use Gotphoto\Logging\Formatter;
use Gotphoto\Logging\NewrelicProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
};
