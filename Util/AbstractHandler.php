<?php

namespace LxRmp\Util;

use LxRmp\LxRmp;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;

abstract class AbstractHandler implements SubscriberInterface
{
    /** @var ModelManager */
    protected $entityManager;

    /** @var $connection Connection */
    private $connection;

    /**
     * AbstractService constructor.
     * @param ModelManager $entityManager
     * @param Connection $connection
     */
    public function __construct(
        ModelManager $entityManager,
        Connection $connection
    ){
        $this->entityManager = $entityManager;
        $this->connection = $connection;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents():array
    {
        return [
            LxRmp::PLUGIN_UPDATE => 'onPluginUpdate',
            LxRmp::PLUGIN_UNINSTALL => 'onPluginUninstall'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    abstract public function onPluginUpdate(\Enlight_Event_EventArgs $args);

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    abstract public function onPluginUninstall(\Enlight_Event_EventArgs $args);

    /**
     * @return SchemaTool
     */
    protected function getNewSchema():SchemaTool
    {
        return new SchemaTool($this->entityManager);
    }

    /**
     * @param $class
     * @return string
     */
    protected function getEntityClassTable($class):string
    {
        return $this->entityManager->getClassMetadata($class)->getTableName();
    }
}
