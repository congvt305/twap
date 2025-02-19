<?php
/**
 * Created by Eguana.
 * User: Brian
 * Date: 2020-06-23
 * Time: 오후 3:00
 */

namespace Amore\Sap\Controller\Adminhtml\SapOrder;

use Amore\Sap\Exception\ShipmentNotExistException;
use CJ\Middleware\Model\SapRequest;
use Amore\Sap\Model\SapOrder\SapOrderConfirmData;
use Amore\Sap\Model\Source\Config;
use Amore\Sap\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use CJ\Middleware\Helper\Data as MiddlewareHelper;

class OrderSend extends Action
{
    /**
     * @var SapOrderConfirmData
     */
    private $sapOrderConfirmData;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var SapRequest
     */
    protected $request;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MiddlewareHelper
     */
    protected $middlewareHelper;

    /**
     * @param Action\Context $context
     * @param SapRequest $request
     * @param Logger $logger
     * @param Config $config
     * @param OrderRepositoryInterface $orderRepository
     * @param SapOrderConfirmData $sapOrderConfirmData
     * @param ManagerInterface $eventManager
     * @param MiddlewareHelper $middlewareHelper
     */
    public function __construct(
        Action\Context $context,
        SapRequest $request,
        Logger $logger,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        SapOrderConfirmData $sapOrderConfirmData,
        ManagerInterface $eventManager,
        MiddlewareHelper $middlewareHelper
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->sapOrderConfirmData = $sapOrderConfirmData;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->logger = $logger;
        $this->config = $config;
        $this->middlewareHelper = $middlewareHelper;
    }

    public function execute()
    {
        if ($this->config->getLoggingCheck()) {
            $this->logger->info("SAP Send Order Entity Id");
            $this->logger->info($this->getRequest()->getParam('order_id'));
        }
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $orderSendCheck = $order->getData('sap_order_send_check');

        if ($orderSendCheck == null) {
            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_BEFORE);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/order/index');

        try {
            $orderSendData = $this->sapOrderConfirmData->singleOrderData($order->getIncrementId());

            if (isset($orderSendData['request']['input']['itHead'][0])) {
                $this->sapOrderConfirmData->setReturnOrderData(
                    $orderSendData['request']['input']['itHead'][0],
                    $order
                );
            }
            if (isset($orderSendData['request']['input']['itHead'][0])) {
                $orderSendData['request']['input']['itItem'] = $this->sapOrderConfirmData->setReturnItemOrderData(
                    $orderSendData['request']['input']['itItem'],
                    $order
                );
            }

            if ($this->config->getLoggingCheck()) {
                $this->logger->info("Single Order Send Data");
                $this->logger->info($this->middlewareHelper->serializeData($orderSendData));
            }

            $result = $this->request->sendRequest($this->middlewareHelper->serializeData($orderSendData), $order->getStoreId(), 'confirm');

            if ($this->config->getLoggingCheck()) {
                $this->logger->info("Single Order Result Data");
                $this->logger->info($this->middlewareHelper->serializeData($result));
            }

            $this->eventManager->dispatch(
                \Amore\CustomerRegistration\Model\POSSystem::EGUANA_BIZCONNECT_OPERATION_PROCESSED,
                [
                    'topic_name' => 'amore.sap.order.send.request',
                    'direction' => 'outgoing',
                    'to' => "SAP",
                    'serialized_data' => $this->middlewareHelper->serializeData($orderSendData),
                    'status' => $this->successCheck($orderSendData),
                    'result_message' => $this->middlewareHelper->serializeData($result)
                ]
            );

            if (!$this->successCheck($orderSendData) && array_key_exists('message', $orderSendData)) {
                $this->messageManager->addErrorMessage(__($orderSendData['message']));
                return $resultRedirect;
            }

            $responseHandled = $this->request->handleResponse($result);
            if ($responseHandled === null) {
                $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
                $order->setStatus('sap_fail');
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while sending order data to SAP. No response')
                );
            } else {
                $outData = [];
                if (isset($responseHandled['data']['output']['outdata'])){
                    $outData = $responseHandled['data']['output']['outdata'];
                }
                if (isset($responseHandled['success']) && $responseHandled['success'] == true) {
                    foreach ($outData as $data) {
                        if ($data['retcod'] == 'S') {
                            $order->setStatus('sap_processing');
                            if ($orderSendCheck == 0 || $orderSendCheck == 2) {
                                $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_RESENT_TO_SAP_SUCCESS);
                            } else {
                                $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_SUCCESS);
                            }
                            $this->messageManager->addSuccessMessage(__('Order %1 sent to SAP Successfully.', $order->getIncrementId()));
                        } else {
                            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
                            $order->setStatus('sap_fail');
                            $this->messageManager->addErrorMessage(
                                __(
                                    'Error returned from SAP for order %1. Error code : %2. Message : %3',
                                    $order->getIncrementId(),
                                    $data['ugcod'],
                                    $data['ugtxt']
                                )
                            );
                        }
                    }
                } else {
                    $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
                    $order->setStatus('sap_fail');
                    $this->messageManager->addErrorMessage(
                        __(
                            'Error returned from SAP for order %1. Message : %2',
                            $order->getIncrementId(),
                            $responseHandled['message']
                        )
                    );
                }
            }
        } catch (ShipmentNotExistException $e) {
            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
            $order->setStatus($order->getStatus());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (NoSuchEntityException $e) {
            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
            $order->setStatus($order->getStatus());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (LocalizedException $e) {
            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
            $order->setStatus($order->getStatus());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $order->setData('sap_order_send_check', SapOrderConfirmData::ORDER_SENT_TO_SAP_FAIL);
            $order->setStatus($order->getStatus());
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $this->orderRepository->save($order);

        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amore_Sap::sap');
    }

    public function successCheck($result)
    {
        if (array_key_exists('code', $result)) {
            return 0;
        } else {
            return 1;
        }
    }
}
