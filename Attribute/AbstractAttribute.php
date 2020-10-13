<?php

namespace LxRmp\Attribute;

use LxRmp\Util\AbstractHandler;
use Doctrine\ORM\NoResultException;
use Shopware\Bundle\AttributeBundle\Service\CrudService;

abstract class AbstractAttribute extends AbstractHandler
{
    /**
     * CrudService
     *
     * @var CrudService
     */
    private $crudService;

    /**
     * Name of the database table
     *
     * @return string
     */
    abstract protected function getTableName();

    /**
     * List of fields
     *
     * @return array
     */
    abstract protected function getFields();

    /**
     * Perform update for this plugin
     *
     * @param string $oldVersion Old Version
     */
    abstract protected function doUpdate($oldVersion);

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return mixed|void
     */
    public function onPluginUpdate(\Enlight_Event_EventArgs $args)
    {
        $oldVersion = $args->get('oldVersion');
        $this->doUpdate($oldVersion);
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return mixed|void
     */
    public function onPluginUninstall(\Enlight_Event_EventArgs $args){}

    /**
     * @param $name
     * @throws \Exception
     */
    protected function addField($name):void
    {
        $this->updateField($name);
    }

    /**
     * @param $name
     * @throws \Exception
     */
    protected function updateField($name):void
    {
        $def = $this->getDefinition($name);
        $service = $this->getCrudService();
        $def[2]['displayInBackend'] = true;
        $service->update($this->getTableName(), $def[0], $def[1], $def[2]);
    }

    /**
     * @param $name
     * @throws \Exception
     */
    protected function deleteField($name):void
    {
        $service = $this->getCrudService();
        if ($service->get($this->getTableName(), $name)) {
            $service->delete($this->getTableName(), $name);
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws NoResultException
     */
    protected function getDefinition($name)
    {
        foreach ($this->getFields() as $def) {
            if ($def[0] == $name) {
                return $def;
            }
        }
        throw new NoResultException();
    }

    /**
     * @return CrudService
     */
    protected function getCrudService():CrudService
    {
        if (!$this->crudService instanceof CrudService) {
            $this->crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
        }
        return $this->crudService;
    }
}
