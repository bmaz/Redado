<?php

namespace Redado\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Redado\CoreBundle\DependencyInjection\RedadoCoreExtension;

class RedadoCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->registerExtension(new RedadoCoreExtension());
    }
}
