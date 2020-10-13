<?php
namespace LxRmp\Subscriber;

use Enlight\Event\SubscriberInterface;
use LxRmp\Components\Connector;
use LxRmp\Components\Data\Response;
use LxRmp\LxRmp;
use LxRmp\Services\PaymentService;

class RiskManagement implements SubscriberInterface
{
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * RiskManagement constructor.
     * @param Connector $connector
     * @param PaymentService $paymentService
     */
    public function __construct(
        Connector $connector,
        PaymentService $paymentService
    ){
        $this->connector = $connector;
        $this->paymentService = $paymentService;
    }
    
    /**
     * @return array
     */
    public static function getSubscribedEvents():array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMeans',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'onSavePayment',
            'Enlight_Controller_Action_PreDispatch_Frontend_Account' => 'onSavePaymentAccount'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterPaymentMeans(\Enlight_Event_EventArgs $args):void
    {
        $timeOfVerification =  $this->getTimeOfVerification();
        if($timeOfVerification === 0){
            /** @var array $availableMethods */
            $availableMethods = $args->getReturn();

            /** @var Response $response */
            try{
                $response = $this->connector->getRating();
            }catch (\Exception $exception){
                $response = null;
            }

            if($response !== null && $response->wasSuccessful()){
                foreach ($availableMethods as $index => $paymentMethod) {
                    if(!$response->filterPayments($paymentMethod['name'])){
                        unset($availableMethods[$index]);
                    }
                }
                $args->setReturn($availableMethods);
            }
        }
    }

    public function onSavePayment(\Enlight_Event_EventArgs $eventArgs):void
    {
        $timeOfVerification =  $this->getTimeOfVerification();
        /** @var \Enlight_Controller_Action $controller */
        $controller = $eventArgs->getSubject();
        $view = $controller->View();
        if(
            $controller->Request()->getActionName() === 'saveShippingPayment' &&
            $timeOfVerification === 1
        ){
            $paymentId = $controller->Request()->getPost('payment');
            if(!$this->paymentService->isHarmless($paymentId)){
                $paymentName = $this->paymentService->getPaymentByID($paymentId);
                if($paymentName !==  null){
                    /** @var Response $response */
                    try{
                        $response = $this->connector->getRating();
                    }catch (\Exception $exception){
                        $response = null;
                    }
                    if($response !== null && $response->wasSuccessful()){
                        if(!$response->filterPayments($paymentName)){
                            Shopware()->Session()->showError = true;
                            $controller->redirect([
                                'controller' => 'checkout',
                                'action' => 'shippingPayment',
                            ]);
                            return;
                        }
                    }
                }
            }
        }elseif ($controller->Request()->getActionName() === 'shippingPayment' && ((bool)Shopware()->Session()->showError)){
            Shopware()->Session()->showError = false;
            $view->assign('sErrorFlag', true);
            $sErrorMessages[] = Shopware()->Snippets()->getNamespace('frontend/checkout/error_messages')
                ->get('LnxRmpPaymentCheckoutError', 'Please Select another payment method.');
            $view->assign('sErrorMessages', $sErrorMessages);
        }
        if( $controller->Request()->getActionName() === 'finish' ){
            $userData = Shopware()->Modules()->Admin()->sGetUserData();
            if(!$this->paymentService->isHarmless($userData['additional']['payment']['id'])){
                $paymentName = $userData['additional']['payment']['name'];
                if($paymentName !==  null){
                    /** @var Response $response */
                    try{
                        $response = $this->connector->getRating();
                    }catch (\Exception $exception){
                        $response = null;
                    }
                    if($response !== null && $response->wasSuccessful()){
                        if(!$response->filterPayments($paymentName)){
                            Shopware()->Session()->showError = true;
                            $controller->redirect([
                                'controller' => 'checkout',
                                'action' => 'shippingPayment',
                            ]);
                            return;
                        }
                    }
                }
            }
        }
    }

    public function onSavePaymentAccount(\Enlight_Event_EventArgs $eventArgs):void
    {
        $timeOfVerification =  $this->getTimeOfVerification();
        /** @var \Enlight_Controller_Action $controller */
        $controller = $eventArgs->getSubject();
        $view = $controller->View();
        if(
            $controller->Request()->getActionName() === 'savePayment' &&
            $timeOfVerification === 1
        ){
            $post = $controller->Request()->getPost('register');
            if(is_array($post) && array_key_exists('payment', $post)){
                $paymentId = (int)$post['payment'];
            }else{
                $paymentId = null;
            }
            if(!$this->paymentService->isHarmless($paymentId)){
                $paymentName = $this->paymentService->getPaymentByID($paymentId);
                if($paymentName !==  null){
                    /** @var Response $response */
                    try{
                        $response = $this->connector->getRating();
                    }catch (\Exception $exception){
                        $response = null;
                    }
                    if($response !== null && $response->wasSuccessful()){
                        if(!$response->filterPayments($paymentName)){
                            Shopware()->Session()->showError = true;

                            $controller->redirect([
                                'controller' => 'account',
                                'action' => 'payment',
                            ]);
                            return;
                        }
                    }
                }
            }
        }elseif ($controller->Request()->getActionName() === 'payment' && ((bool)Shopware()->Session()->showError)){
            Shopware()->Session()->showError = false;
            $view->assign('sErrorFlag', true);
            $sErrorMessages[] = Shopware()->Snippets()->getNamespace('frontend/account/error_messages')
                ->get('LnxRmpPaymentAccountError', 'Please Select another payment method.');
            $view->assign('sErrorMessages', $sErrorMessages);
        }
    }

    protected function getTimeOfVerification():?int
    {
       return Shopware()->Config()->getByNamespace(LxRmp::PLUGIN_NAMESPACE, 'timeOfVerification');
    }
}