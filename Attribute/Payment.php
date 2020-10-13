<?php

namespace LxRmp\Attribute;

use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

class Payment extends AbstractAttribute
{

    /**
     * Name of the database table
     *
     * @return string
     */
    protected function getTableName():string
    {
        return 's_core_paymentmeans_attributes';
    }

    /**
     * @return array
     */
    protected function getFields():array
    {
        $fields = array();

        $fields[] = [
          'harmless',
          TypeMapping::TYPE_BOOLEAN,
          [
              'label' => 'Unbedenklich',
              'position' => 10
          ]
        ];
        return $fields;
    }

    /**
     * @param string $oldVersion
     */
    protected function doUpdate($oldVersion):void
    {
        if(version_compare('1.0.3', $oldVersion, '>')){
            try{
                $this->addField('harmless');
            }catch (\Exception $exception){
            }
        }
    }
}
