<?php

namespace Amore\PointsIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Amore\PointsIntegration\Model\Source\Config as PointConfig;
use Magento\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Sales\Model\Order;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Framework\Exception\CouldNotSaveException;
use Amore\PointsIntegration\Logger\Logger as AmoreLogger;

class POSSetOrderCancelSend implements ObserverInterface
{
    /**
     * @var PointConfig
     */
    protected $pointConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CollectionFactory
     */
    protected $rmaCollectionFactory;

    /**
     * @var AmoreLogger
     */
    protected $logger;

    /**
     * @param PointConfig $pointConfig
     * @param OrderRepository $orderRepository
     * @param CollectionFactory $rmaCollectionFactory
     * @param AmoreLogger $logger
     */
    public function __construct(
        PointConfig       $pointConfig,
        OrderRepository   $orderRepository,
        CollectionFactory $rmaCollectionFactory,
        AmoreLogger       $logger
    ) {
        $this->pointConfig = $pointConfig;
        $this->orderRepository = $orderRepository;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @throws CouldNotSaveException
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Order $order
         */
        $order = $observer->getEvent()->getOrder();

        $moduleActive = $this->pointConfig->getActive($order->getStore()->getWebsiteId());
        $cancelledOrderToPos = $this->pointConfig->getPosCancelledOrderActive($order->getStore()->getWebsiteId());
        if ($moduleActive & $cancelledOrderToPos) {
            if ($this->isCanceledOrder($order) &&
                !$this->isCancelSent($order) &&
                $this->isOrderPaid($order) &&
                !$this->getRMA($order)
            ) {
                try {
                    $this->logger->info('Before save flag pos_order_cancel_send with id: ' . $order->getIncrementId());
                    $order->setData('pos_order_cancel_send', true);
                    $this->orderRepository->save($order);
                    $this->logger->info('After save flag pos_order_cancel_send with id: ' . $order->getIncrementId());
                } catch (\Exception $exception) {
                    $this->logger->error('Error save flag pos_order_cancel_send with id: ' . $order->getIncrementId() . ' ' . $exception->getMessage());
                    throw new CouldNotSaveException(__("Order can not be saved"));
                }
            }
        }
    }

    /**
     * @param $order
     * @return bool
     */
    private function isCanceledOrder($order): bool
    {
        return $order->isCanceled() || $order->getState() === Order::STATE_CLOSED;
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function isOrderPaid(Order $order): bool
    {
        return $order->getData('pos_order_paid_sent') || $order->getData('pos_order_paid_send');
    }

    /**
     * @param Order $order
     * @return mixed
     */
    private function isCancelSent(Order $order)
    {
        return $order->getData('pos_order_cancel_sent') || $order->getData('pos_order_cancel_send');
    }

    /**
     * @param Order $order
     * @return \Magento\Framework\DataObject[]
     */
    private function getRMA(Order $order): array
    {
        $rmaCollection = $this->rmaCollectionFactory->create();
        $rmaCollection->addFieldToFilter('order_id', $order->getEntityId());

        return $rmaCollection->getItems();
    }
}
