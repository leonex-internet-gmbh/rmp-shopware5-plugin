<?php

use LxRmp\Components\Connector;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_LxRmpValidate extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * Sucess if Bad-Request -> empty Body, but authorized [400]
     * Fail if HTTP_UNAUTHORIZED [401]
     */
    public function validateAction():void
    {
        /** @var Connector $connector */
        $connector = Shopware()->Container()->get('lx_rmp.component.connector');
        $response = $connector->validateConnection();
        $this->View()->assign('response', 'Something went wrong, please see logfiles!');

        if($response !== null){
            $this->View()->assign('response', 'Success!');
            if ((int) $response === Response::HTTP_BAD_REQUEST) {
                $this->View()->assign('response', 'Success!');
            }
        }
    }
}