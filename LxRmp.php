<?php
namespace LxRmp;

use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class LxRmp
 *
 * @package LxRmp
 * @author fseeger
 */
class LxRmp extends Plugin
{

    public const PLUGIN_NAMESPACE = 'LxRmp';

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('index.plugin_dir', $this->getPath());
        parent::build($container);
    }
}