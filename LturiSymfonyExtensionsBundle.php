<?php

namespace Lturi\SymfonyExtensions;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LturiSymfonyExtensionsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Let's inject the user entity, if doctrine is available
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                    [ 'Lturi\SymfonyExtensions\Framework\Entity' ],
                    [ realpath(__DIR__.'/Framework/Entity') ],
                    [],
                    false,
                    [ 'LturiSymfonyExtensions' => 'Lturi\SymfonyExtensions\Framework\Entity' ]
                )
            );
        }
    }
}