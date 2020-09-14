<?php
namespace LxRmp\Components\Data;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;

/**
 * Class QuoteModel
 *
 * Get additional customer information from the db.
 *
 * @package LxRmp\Components\Data
 * @author fseeger
 */
class QuoteModel
{

    /** @var ModelManager */
    protected $modelManager;

    /**
     * QuoteModel constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * Get the number of canceled orders.
     *
     * @param $userID
     *
     * @return int
     */
    public function getNumberOfCanceledOrders($userID):int
    {
        return $userID < 1 ? 0 : $this->getNumberOf(
            $userID,
            [
                'status = '.Status::ORDER_STATE_CANCELLED_REJECTED
            ]
        );
    }

    /**
     * Get the number of completed orders.
     *
     * @param $userID
     *
     * @return int
     */
    public function getNumberOfCompletedOrders($userID):int
    {
        $conditions = [
            'status = '.Status::ORDER_STATE_COMPLETED,
            'cleared = '.Status::PAYMENT_STATE_COMPLETELY_PAID
        ];
        return $userID < 1 ? 0 : $this->getNumberOf($userID, $conditions);
    }

    /**
     * Get the number of unpaid orders.
     *
     * @param $userID
     *
     * @return int
     */
    public function getNumberOfUnpaidOrders($userID):int
    {
        $conditions = [
            'cleared = '.Status::PAYMENT_STATE_PARTIALLY_PAID,
            'cleared = '.Status::PAYMENT_STATE_1ST_REMINDER,
            'cleared = '.Status::PAYMENT_STATE_2ND_REMINDER,
            'cleared = '.Status::PAYMENT_STATE_3RD_REMINDER,
            'cleared = '.Status::PAYMENT_STATE_ENCASHMENT,
            'cleared = '.Status::PAYMENT_STATE_OPEN,
            'cleared = '.Status::PAYMENT_STATE_DELAYED,
            'cleared = '.Status::PAYMENT_STATE_NO_CREDIT_APPROVED,
            'cleared = '.Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
        ];

        return $userID < 1 ? 0 : $this->getNumberOf($userID, $conditions);
    }

    /**
     * Get the number of the orders by specific states and user.
     *
     * @param $userID
     * @param array $conditions
     *
     * @return int
     */
    protected function getNumberOf($userID, array $conditions):int
    {
        $flag = 0;

        /** @var \Doctrine\DBAL\Query\QueryBuilder $builder */
        $builder = $this->modelManager->getDBALQueryBuilder();

        $builder->select('ordernumber')
            ->from('s_order')
            ->where('userID = :userID');


        foreach ($conditions as $condition){
            if($flag == 0){
                $builder->where($condition);
                $flag = 1;
            }else{
                $builder->orWhere($condition);
            }
        }

        $builder->setParameter('userID', $userID);

        /** @var \Doctrine\DBAL\Driver\Statement|int $stmt */
        $stmt = $builder->execute();

        return $stmt->rowCount();
    }
}