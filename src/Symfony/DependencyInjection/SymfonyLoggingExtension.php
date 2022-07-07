<?php

declare(strict_types=1);

namespace GDXbsv\PServiceBusBundle\DependencyInjection;

use Gotphoto\Logging\ExceptionContext\ExceptionContext;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class SymfonyLoggingExtension extends ConfigurableExtension
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
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

        $container
            ->registerForAutoconfiguration(ExceptionContext::class)
            ->addTag('gotphoto_logging.exception_context')
            ->setLazy(true);
    }
}
