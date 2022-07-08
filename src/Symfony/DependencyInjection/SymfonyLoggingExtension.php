<?php

declare(strict_types=1);

namespace Gotphoto\Logging\Symfony\DependencyInjection;

use Gotphoto\Logging\ExceptionContext\ExceptionContext;
use Gotphoto\Logging\Formatter;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 */
final class SymfonyLoggingExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__.'/../config')
        );
        $loader->load('services.php');
        $env = $container->getParameter('kernel.environment');
        try {
            $loader->load("services_{$env}.php");
        } catch (FileLocatorFileNotFoundException $e) {
            //ignore if no file for env
        }

        $container->getDefinition(Formatter::class)
            ->setArgument('$applicationName', $configs['app_name'])
            ->setArgument('$environment', $env);

        $container
            ->registerForAutoconfiguration(ExceptionContext::class)
            ->addTag('gotphoto_logging.exception_context')
            ->setLazy(true);
    }
}
