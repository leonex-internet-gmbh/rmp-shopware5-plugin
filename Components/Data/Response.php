<?php
namespace LxRmp\Components\Data;

/**
 * Class Response
 *
 * Manage the response from the api-call.
 * Main goal of this class is a storage of the response in a structured way.
 * Beneath the structuring of the data the class implements a function to filter the payments from the main event.
 *
 * @package LxRmp\Components\Data
 * @author fseeger
 */
class Response
{
    /** @var string */
    protected $status;

    /** @var \stdClass */
    protected $payments;

    /**
     * Response constructor.
     *
     * @param \stdClass $response
     */
    public function __construct(
        \stdClass $response
    ){
        $this->status = $response->status;
        $this->payments = $response->payment_methods;
    }

    /**
     * Get the payments given by the main event as argument and filter them with new conditions from the response.
     *
     * When a payment is marked as unavailable (available != true) then remove this payment from the array.
     * A array with available payments will be returned.
     *
     * @param string $payment
     *
     * @return bool
     */
    public function filterPayments(string $payment):bool
    {
        if(!empty($payment) && is_object($this->payments->$payment)){
            $obj = $this->payments->$payment;
            if($obj->available){
                return true;
            }
            return false;
        }
        return true;
    }

    public function wasSuccessful():bool
    {
        return $this->status === 'success';
    }
}