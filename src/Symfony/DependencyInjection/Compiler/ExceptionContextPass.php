<?php
declare(strict_types=1);

namespace Gotphoto\Logging\Symfony\DependencyInjection\Compiler;

use Gotphoto\Logging\LogstashFormatter;
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
            /** @psalm-suppress ArgumentTypeCoercion, PossiblyNullArgument */
            $reflectionClass = new ReflectionClass($className);
            if (!$reflectionClass->hasMethod('__invoke')) {
                /** @psalm-suppress PossiblyNullOperand */
                throw new \Exception($definition->getClass() . ' has to have __invoke method.');
            }
            $reflectionMethod = $reflectionClass->getMethod('__invoke');

            /** @psalm-suppress UndefinedMethod, PossiblyNullReference, PossiblyUndefinedIntArrayOffset */
            $typehintClassName = $reflectionMethod->getParameters()[0]->getType()->getName();
            /** @psalm-suppress MixedArgument */
            if (!is_subclass_of($typehintClassName, Throwable::class)) {
                /** @psalm-suppress PossiblyNullOperand */
                throw new \Exception(
                    $definition->getClass() . ' has to have __invoke method with argument "is_subclass_of Throwable".',
                );
            }

            $exceptionContextMap[$typehintClassName][] = new Reference($id);
        }

        $container->getDefinition(LogstashFormatter::class)->setArgument(
            '$exceptionContextProviderMap',
            $exceptionContextMap,
        );

        $container->getDefinition(OtelFormatter::class)->setArgument(
            '$exceptionContextProviderMap',
            $exceptionContextMap,
        );
    }
}
