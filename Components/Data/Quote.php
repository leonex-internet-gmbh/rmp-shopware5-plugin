<?php
namespace LxRmp\Components\Data;

/**
 * Class Quote
 *
 * The quote handles the data from the customer and the basket as a structured object.
 *
 * @package LxRmp\Components\Data
 * @author fseeger
 */
class Quote
{
    /** @var mixed */
    protected $user;

    /** @var \sBasket */
    protected $basket;

    /** @var integer*/
    protected $justifiable;

    /** @var mixed */
    protected $basketData;

    /** @var QuoteModel */
    protected $quoteModel;

    /**
     * Quote constructor.
     *
     * @param array $user
     * @param \sBasket $basket
     * @param $justifiable
     * @throws \Exception
     */
    public function __construct(
        array $user,
        \sBasket $basket,
        $justifiable
    ){
        $this->user = json_decode(json_encode($user), FALSE);
        $this->basket = $basket;
        $this->justifiable = $justifiable;
        $this->basketData = json_decode(json_encode($basket->sGetBasketData()), FALSE);
        $this->quoteModel = Shopware()->Container()->get('lx_rmp.component.quotemodel');
    }

    /**
     * Return the normalized quote and trigger a filter event.
     *
     * @throws \Exception
     * @return array
     */
    public function getNormalizedQuote():array
    {
        $quote = $this->normalizeQuote();

        $quote = Shopware()->Events()->filter('Lx_Risk_Management_Quote_Data_FilterResult', $quote, ['quote' => $this]);

        return $quote;
    }

    /**
     * Structure the given information in a new structured way.
     * The structure correlate with required api-structure.
     *
     * @return array
     */
    protected function normalizeQuote():array
    {
        return [
            'customerSessionId' => $this->getSessionID(),
            'justifiableInterest'  => $this->justifiable,
            'consentClause'        => true,
            'billingAddress'       => $this->getBillingAddress(),
            'shippingAddress'       => $this->getShippingAddress(),
            'quote' => $this->getQuote(),
            'customer' => $this->getCustomerData(),
            'orderHistory' => $this->getOrderHistory()
        ];
    }

    /**
     * Get the gender by the given salutation.
     *
     * @param $salutation
     *
     * @return mixed
     */
    protected function gender($salutation)
    {
        $salutationToGender = array(
          'mr' => 'm',
        );
        return $salutationToGender[$salutation];
    }

    /**
     * Adjust the data from the billing address.
     *
     * @return array
     */
    protected function getBillingAddress():array
    {
        $billingAddress = $this->user->billingaddress;
        return [
            'gender'               => $this->gender($billingAddress->salutation),
            'lastName'             => $billingAddress->lastname,
            'firstName'            => $billingAddress->firstname,
            'dateOfBirth'          => $this->user->additional->user->birthday,
            'birthName'            => '',//$billingAddress->lastname,
            'street'               => $billingAddress->street,
            'street2'              => '', // optional (needed for Packstation)
            'zip'                  => $billingAddress->zipcode,
            'city'                 => $billingAddress->city,
            'country'              => 'de',
        ];
    }
    /**
     * Adjust the data from the shipping address.
     *
     * @return array
     */
    protected function getShippingAddress():array
    {
        $shippingAddress = $this->user->shippingaddress;
        return [
            'gender'               => $this->gender($shippingAddress->salutation),
            'lastName'             => $shippingAddress->lastname,
            'firstName'            => $shippingAddress->firstname,
            'dateOfBirth'          => $this->user->additional->user->birthday,
            'birthName'            => '',//$billingAddress->lastname,
            'street'               => $shippingAddress->street,
            'street2'              => '', // optional (needed for Packstation)
            'zip'                  => $shippingAddress->zipcode,
            'city'                 => $shippingAddress->city,
            'country'              => 'de',
        ];
    }

    /**
     * Return the user session identifier.
     *
     * @return mixed
     */
    protected function getSessionID()
    {
        return $this->user->additional->user->sessionID;
    }

    /**
     * Get the number and email from the customer.
     *
     * @return array
     */
    protected function getCustomerData():array
    {
        return [
            'number' => $this->user->additional->user->customernumber,
            'email' => $this->user->additional->user->email
        ];
    }

    /**
     * Get the item quote.
     * Includes the total amount and a array of basket items.
     *
     * @return array
     */
    protected function getQuote():array
    {
        return [
            'items' => $this->getQuoteItems(),
            'totalAmount' => $this->basket->sGetAmount()['totalAmount'], // 3.50 == shipping costs
        ];
    }

    /**
     * Get the items from the basket as array.
     *
     * @return array
     */
    protected function getQuoteItems():array
    {
        $quoteItems = array();
        $items = $this->basketData->content;
        foreach ($items as $item){
            $quoteItems[] = [
                'sku' => $item->ordernumber,
                'quantity' => $item->quantity,
                'price' => (float)$item->price,
                'rowTotal' => (float)$item->amount
            ];
        }

        return $quoteItems;
    }

    /**
     * Get the customer history from the quote model.
     *
     * @return array
     */
    protected function getOrderHistory():array
    {
        $customerNumber = $this->user->additional->user->customernumber;
        return [
            'numberOfCanceledOrders' => $this->quoteModel->getNumberOfCanceledOrders($customerNumber),
            'numberOfCompletedOrders' => $this->quoteModel->getNumberOfCompletedOrders($customerNumber),
            'numberOfUnpaidOrders' => $this->quoteModel->getNumberOfUnpaidOrders($customerNumber),
            'numberOfOutstandingOrders' => 0,
        ];
    }

    /**
     * Create a md5 from the basket and customer to block recurring events.
     *
     * @return string
     */
    public function getQuoteHash():string
    {
        $user = $this->user;
        $basketData = $this->basketData;
        $billingAddress = $user->billingaddress;

        $hash = $user->additional->user->sessionID;
        $hash .= $billingAddress->lastname;
        $hash .= $billingAddress->firstname;
        $hash .= $billingAddress->street;
        $hash .= $billingAddress->zipcode;
        $hash .= $billingAddress->city;

        $items = $basketData->content;

        foreach ($items as $item){
            $hash .= $item->ordernumber;
            $hash .= $item->quantity;
        }

        return md5($hash);
    }

    /**
     * compare a given md5 with a new generated from the quote.
     *
     * @param $hash
     *
     * @return bool
     */
    public function hashCompare($hash):bool
    {
        return $hash === $this->getQuoteHash();
    }
}