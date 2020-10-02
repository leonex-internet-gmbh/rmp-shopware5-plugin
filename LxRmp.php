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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LxRmpValidate' => 'onControllerLxRmpValidate'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $eventArgs
     * @return string
     */
    public function onControllerLxRmpValidate(\Enlight_Event_EventArgs $eventArgs)
    {
        return $this->getPath().'/Controller/Backend/LxRmpValidate.php';
    }

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('index.plugin_dir', $this->getPath());
        parent::build($container);
    }
}