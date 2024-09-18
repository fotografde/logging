<?php declare(strict_types=1);

namespace Gotphoto\Logging\Symfony\DependencyInjection\Compiler;

use Gotphoto\Logging\Formatter;
use Gotphoto\Logging\OtelFormatter;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Throwable;

class ExceptionContextPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $exceptionContextMap = [];

        foreach ($container->findTaggedServiceIds('gotphoto_logging.exception_context') as $id => $_tags) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();
            $reflectionClass = new ReflectionClass($className);
            if (!$reflectionClass->hasMethod('__invoke')) {
                throw new \Exception($definition->getClass() . ' has to have __invoke method.');
            }
            $reflectionMethod = $reflectionClass->getMethod('__invoke');
            $typehintClassName = $reflectionMethod->getParameters()[0]->getClass()->getName();
            if (!is_subclass_of($typehintClassName, Throwable::class)) {
                throw new \Exception($definition->getClass() . ' has to have __invoke method with argument "is_subclass_of Throwable".');
            }

            $exceptionContextMap[$typehintClassName][] = new Reference($id);
        }

        $container->getDefinition(Formatter::class)->setArgument('$exceptionContextProviderMap', $exceptionContextMap);

        $container->getDefinition(OtelFormatter::class)->setArgument('$exceptionContextProviderMap', $exceptionContextMap);
    }
}
