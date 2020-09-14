<?php
namespace LxRmp\Services;

use Shopware\Components\Model\ModelManager;

class PaymentService
{
    /** @var ModelManager */
    protected $entityManager;

    public function __construct(ModelManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getPaymentByID(int $paymentId):?string
    {
        $queryBuilder = $this->entityManager->getDBALQueryBuilder();
        $statement = $queryBuilder->select('name')
            ->from('s_core_paymentmeans')
            ->where('id = :id')
            ->setParameter('id', $paymentId)
            ->execute();
        $payment = $statement->fetch();
        if($payment){
            return $payment['name'];
        }
        return null;
    }

}