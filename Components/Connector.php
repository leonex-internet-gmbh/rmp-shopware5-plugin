<?php
namespace LxRmp\Components;

use LxRmp\Components\Data\Quote;
use LxRmp\Components\Data\Response;
use LxRmp\LxRmp;
use Monolog\Logger;
use Shopware\Components\HttpClient\HttpClientInterface;

/**
 * Class Connector
 *
 * @package LxRmp\Components
 * @author fseeger
 */
class Connector {

    /** Dt.: Geschäftsanbahnung */
    private const JUSTIFIABLE_INTEREST_BUSINESS_INITIATION = 3;

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Connector constructor.
     * @param HttpClientInterface $client
     * @param Logger $logger
     */
    public function __construct(
        HttpClientInterface $client,
        Logger $logger
    ){
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Get all required data and call the api via post-method.
     *
     * Create a new Quote to check if a response is already stored.
     * If a response is found, then create a new Response Object from the old api-call.
     * If no response is found then create a new api object and pass this object a normalized quote object.
     * As a result of the api-call a new Response object will be returned and stored into the session.
     *
     * @param array|null $data
     * @throws \Exception
     * @return Response
     */
    public function getRating(array $data = null):Response
    {
        $quote = $this->getQuote();
        if($this->justifyInterest($quote)){

            try {
                if($data === null){
                    $data = $quote->getNormalizedQuote();
                }
                $response = $this->client->post(
                    $this->getApiUrl(),
                    $this->buildHeader(),
                    json_encode($data)
                );

                $this->storeHash($quote->getQuoteHash());
                $this->storeResponse($response);
            }catch (\Exception $exception) {
                $this->logger->addError($exception->getMessage());
            }
        }
        return $this->loadResponse();
    }

    /**
     * Get basket and user data from the core shopware modules and create a new quote.
     *
     * @throws \Exception
     * @return Quote
     */
    public function getQuote():Quote
    {
        $sAdmin = Shopware()->Modules()->Admin();
        $user = $sAdmin->sGetUserData();
        $basket = Shopware()->Modules()->Basket();

        return new Quote(
            $user,
            $basket,
            self::JUSTIFIABLE_INTEREST_BUSINESS_INITIATION
        );
    }

    /**
     * Get the API-url from the plugin config.
     * @return string
     */
    protected function getApiUrl():string
    {
        return Shopware()->Config()->getByNamespace(LxRmp::PLUGIN_NAMESPACE, 'apiUrl');
    }

    /**
     * Get the API-key from the plugin config.
     *
     * @return string
     */
    protected function getApiKey():string
    {
        return Shopware()->Config()->getByNamespace(LxRmp::PLUGIN_NAMESPACE, 'apiKey');
    }

    /**
     * Check if the basket and customer data has any changes.
     * If not then load the old response from the session.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    protected function justifyInterest(Quote $quote):bool
    {
        return !$quote->hashCompare($this->loadHash());
    }

    /**
     * Store the response from the api-call.
     *
     * @param $response
     */
    protected function storeResponse( \Shopware\Components\HttpClient\Response $response):void
    {
        Shopware()->Session()->lxRmpResponse = $response->getBody();
    }

    /**
     * Get the response from the session and create a new Response object.
     *
     * @return Response|null
     */
    protected function loadResponse():? Response
    {
        $response = Shopware()->Session()->lxRmpResponse;
        if($response !== null){
            return new Response(json_decode($response));
        }
        return null;
    }

    /**
     * Store the hash into the session.
     *
     * @param $hash
     */
    protected function storeHash($hash):void
    {
        Shopware()->Session()->lxRmpHash = $hash;
    }

    /**
     * Load the hash from the session.
     *
     * @return mixed
     */
    protected function loadHash()
    {
        return Shopware()->Session()->lxRmpHash;
    }

    /**
     * @return array
     */
    protected function buildHeader():array
    {
        return [
            'X-AUTH-KEY' => $this->getApiKey(),
            'Content-Type' => 'application/json; charset=utf-8'
        ];
    }

    /**
     * @return string|null
     */
    public function validateConnection():?string
    {
        try {
            $data = '{"justifiableInterest":3,"consentClause":true,"billingAddress":{"gender":"m","lastName":"Eins","firstName":"Uno","dateOfBirth":"1960-07-07","birthName":"Vierundzwanzig","street":"Hellersbergstr. 14","street2":"","zip":"41460","city":"Neuss","country":"de"},"quote":{"items":[{"sku":"mpd003","quantity":4,"price":140,"rowTotal":560}],"totalAmount":560},"customer":{"number":"139","email":"myemail1.testmail@example.com"},"orderHistory":{"numberOfCanceledOrders":0,"numberOfCompletedOrders":0,"numberOfUnpaidOrders":0,"numberOfOutstandingOrders":0}}';
            $response = $this->client->post(
                $this->getApiUrl(),
                $this->buildHeader(),
                $data
            );
            $this->logger->addInfo('<pre>'.print_r($response->getHeaders().'</pre>'));
            $this->logger->addInfo('Status-Code: '. $response->getStatusCode());
            return $response->getStatusCode();
        }catch (\Exception $exception) {
            $this->logger->addError($exception->getMessage());
        }
        return null;
    }
}