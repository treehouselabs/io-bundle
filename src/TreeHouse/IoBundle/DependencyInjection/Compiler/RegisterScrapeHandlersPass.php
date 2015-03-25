<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterScrapeHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tree_house.io.scrape.scraper_factory');

        foreach ($container->findTaggedServiceIds('tree_house.io.scrape.handler') as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Service "%s" must define the "alias" attribute on "tree_house.io.scrape.handler" tags.', $id)
                    );
                }

                $definition->addMethodCall('registerHandler', [new Reference($id), $attributes['alias']]);
            }
        }
    }
}
