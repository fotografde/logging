<?php
declare(strict_types=1);

namespace Gotphoto\Logging\Symfony;

use Gotphoto\Logging\Symfony\DependencyInjection\Compiler\ExceptionContextPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SymfonyLoggingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExceptionContextPass());
    }
}
