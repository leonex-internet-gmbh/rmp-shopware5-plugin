<?php

namespace LxRmp\Controller\Backend;

use LxRmp\Components\Connector;
use Symfony\Component\HttpFoundation\Response;

class LxRmpValidate extends \Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * LxRmpValidate constructor.
     * @param Connector $connector
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;

        parent::__construct();
    }

    /**
     * Sucess if Bad-Request -> empty Body, but authorized [400]
     * Fail if HTTP_UNAUTHORIZED [401]
     */
    public function validateAction():void
    {
        $response = $this->connector->validateConnection();
        $this->View()->assign('response', 'Something went wrong, please see logfiles!');

        if($response !== null){
            $this->View()->assign('response', 'Success!');
            if ((int) $response === Response::HTTP_BAD_REQUEST) {
                $this->View()->assign('response', 'Success!');
            }
        }
    }
}