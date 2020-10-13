<?php
namespace LxRmp;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
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
    public const PLUGIN_UPDATE = self::PLUGIN_NAMESPACE.'_Update';
    public const PLUGIN_UNINSTALL = self::PLUGIN_NAMESPACE.'_Uninstall';

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

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $context->getPlugin()->setVersion('1.0.0');
    }

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        $eventManager = $this->getEventManager();
        $eventManager->notify(self::PLUGIN_UPDATE, ['oldVersion' => $context->getCurrentVersion()]);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $eventManager = $this->getEventManager();
        $eventManager->notify(self::PLUGIN_UNINSTALL);
    }

    /**
     * @return object
     */
    private function getEventManager()
    {
        return $this->container->get('events');
    }
}