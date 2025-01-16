<?php
declare(strict_types=1);

namespace Gotphoto\Logging\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symfony_logging');

        $rootNode = $treeBuilder->getRootNode();
        /** @psalm-suppress MixedMethodCall, UndefinedMethod */
        $rootNode
            ->children()
                ->scalarNode('app_name')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
