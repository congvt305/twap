<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/22/20
 * Time: 8:56 AM
 */

namespace Eguana\CustomRMA\Plugin;

use Magento\Framework\Exception\AlreadyExistsException;

class ReturnsSubmitPlugin
{
    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma
     */
    private $rmaResource;
    /**
     * @var \Magento\Rma\Model\RmaFactory
     */
    private $rmaFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Rma\Model\ResourceModel\Rma $rmaResource,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->rmaResource = $rmaResource;
        $this->rmaFactory = $rmaFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Rma\Controller\Returns\Submit $subject
     * @param $result
     */
    public function afterExecute(\Magento\Rma\Controller\Returns\Submit $subject, $result)
    {
        $shippingPreference = $subject->getRequest()->getParam('shipping_preference');
        $customPhone = $subject->getRequest()->getParam('customer_custom_phone');
        $rmaCustomerName = $subject->getRequest()->getParam('rma_customer_name');
        if ($shippingPreference) {
            $orderId = (int)$subject->getRequest()->getParam('order_id');
            try {
                $rmaModel = $this->rmaFactory->create();
                $this->rmaResource->load($rmaModel, $orderId, 'order_id');
                $rmaModel->setData('shipping_preference', $shippingPreference);
                $rmaModel->setData('customer_custom_phone', $customPhone);
                $rmaModel->setData('rma_customer_name', $rmaCustomerName);
                $this->rmaResource->save($rmaModel);
            } catch (AlreadyExistsException $e) {
                $this->logger->critical($e->getMessage());
            }
        }
        return $result;
    }
}
