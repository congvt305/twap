<?php

namespace Ecpay\Ecpaypayment\Helper;

use Exception;
use Ecpay\Ecpaypayment\Model\Order as EcpayOrderModel;
use Ecpay\Ecpaypayment\Model\Payment as EcpayPaymentModel;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

include_once('Library/ECPayPaymentHelper.php');

class Data extends AbstractHelper
{
    /**
     * @var EcpayOrderModel
     */
    protected $_ecpayOrderModel;

    /**
     * @var EcpayPaymentModel
     */
    protected $_ecpayPaymentModel;

    /**
     * @var ModuleListInterface
     */
    private $_moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var string
     */
    private $prefix = 'ecpay_';

    /**
     * @var array
     */
    private $errorMessages = array();
    /**
     * @var Library\ECPayInvoiceCheckMacValue
     */
    private $ECPayInvoiceCheckMacValue;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        EcpayOrderModel $ecpayOrderModel,
        EcpayPaymentModel $ecpayPaymentModel,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        \Ecpay\Ecpaypayment\Helper\Library\ECPayInvoiceCheckMacValue $ECPayInvoiceCheckMacValue,
        \Magento\Framework\HTTP\Client\Curl $curl,
        OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_ecpayOrderModel = $ecpayOrderModel;
        $this->_ecpayPaymentModel = $ecpayPaymentModel;
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
        $this->errorMessages = array(
            'invalidPayment' => __('Invalid payment method'),
            'invalidOrder' => __('Invalid order'),
        );
        $this->ECPayInvoiceCheckMacValue = $ECPayInvoiceCheckMacValue;
        $this->curl = $curl;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function getChoosenPayment()
    {
        $session = $this->_ecpayOrderModel->getAdditionalInformation();

        if (empty($session['ecpay_choosen_payment']) === true) {
            return '';
        } else {
            return $session['ecpay_choosen_payment'];
        }
    }

    public function getEcpayConfig($id)
    {
        return $this->_ecpayPaymentModel->getEcpayConfig($id);
    }

    public function getMagentoConfig($id)
    {
        return $this->_ecpayPaymentModel->getMagentoConfig($id);
    }

    public function getErrorMessage($name, $value)
    {
        $message = $this->errorMessages[$name];
        if ($value !== '') {
            return sprintf($message, $value);
        } else {
            return $message;
        }
    }

    public function getPaymentTranslation($payment)
    {
        $text = 'ecpay_payment_text_' . strtolower($payment);
        return __($text);
    }

    public function getRedirectHtml()
    {
        try {

            $sdkHelper = $this->_ecpayPaymentModel->getHelper();

            // Validate the order id
            $orderId = $this->_ecpayOrderModel->getOrderId();
            if (!$orderId) {
                return $this->setFailureStauts($this->getErrorMessage('invalidOrder', ''));
            }

            // Get the order
            $order = $this->_ecpayOrderModel->getOrder($orderId);
            if (!$order) {
                return $this->setFailureStauts($this->getErrorMessage('invalidOrder', ''));
            }

            // Validate choose payment
            $choosenPayment = $this->getChoosenPayment();
            $paymentName = $this->getPaymentTranslation($choosenPayment);
            if ($this->_ecpayPaymentModel->isValidPayment($choosenPayment) === false) {
                return $this->setFailureStauts($this->getErrorMessage('invalidPayment', $paymentName), $order);
            }

            // Validate currency code
            $baseCurrencyCode = $order->getBaseCurrencyCode();
            $orderCurrencyCode = $order->getOrderCurrencyCode();
            if ($baseCurrencyCode !== 'TWD' || $orderCurrencyCode !== 'TWD') {
                return $this->setFailureStauts($order, $this->getErrorMessage('invalidOrder', ''));
            }

            // Update order status and comments
            $createStatus = $this->getMagentoConfig('order_status');

            if ($choosenPayment == 'atm') {
                $createStatus = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            }

            $comment = __('Payment Method: %1', $paymentName);

            $this->setOrderCommentForFront($order, $comment, $createStatus, false);

            // Checkout
            $helperData = array(
                'choosePayment' => $choosenPayment,
                'hashKey' => $this->_ecpayPaymentModel->getEcpayConfigFromStore('hash_key', $order->getStoreId()),
                'hashIv' => $this->_ecpayPaymentModel->getEcpayConfigFromStore('hash_iv', $order->getStoreId()),
                'returnUrl' => $this->_ecpayPaymentModel->getModuleUrl('response'),
                'clientBackUrl' => $this->_ecpayPaymentModel->getMagentoUrl('checkout/onepage/success'),
                'orderId' => $orderId,
                'total' => $order->getGrandTotal(),
                'itemName' => $this->getMerchandizeName($order->getstoreId()),
                'cartName' => 'magento_' . $this->getModuleVersion(),
                'currency' => $orderCurrencyCode,
                'needExtraPaidInfo' => 'Y',
            );

            $sdkHelper->checkout($helperData);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getPaymentResult($paymentData)
    {
        $resultMessage = '1|OK';
        $error = '';
        $orderId = null;

        try {
            $sdkHelper = $this->_ecpayPaymentModel->getHelper();

            // Get valid feedback
            $helperData = array(
                'hashKey' => $this->getEcpayConfig('hash_key'),
                'hashIv'  => $this->getEcpayConfig('hash_iv'),
            );
            $feedback = $sdkHelper->getValidFeedback($helperData);
            unset($helperData);

            if (count($feedback) < 1) {
                throw new Exception('Get ECPay feedback failed.');
            } else {
                $orderId = $sdkHelper->getOrderId($feedback['MerchantTradeNo']);
                $order = $this->_ecpayOrderModel->getOrder($orderId);

                // Check transaction amount and currency
                if (!($order->getOrderCurrencyCode())) {
                    $orderTotal = $order->getGrandTotal();
                    $currency = $order->getOrderCurrencyCode();
                } else {
                    $orderTotal = $order->getBaseGrandTotal();
                    $currency = $order->getBaseCurrencyCode();
                }

                // Check the amounts
                if ($sdkHelper->validAmount($feedback['TradeAmt'], $orderTotal) === false) {
                    throw new Exception(sprintf('Order %s amount are not identical.', $orderId));
                }

                // Get the response status
                $orderStatus = $order->getStatus();
                $createStatus =  $this->getMagentoConfig('order_status');

                $helperData = array(
                    'validState' => ($orderStatus === $createStatus),
                    'orderId' => $orderId,
                );
                $responseStatus = $sdkHelper->getResponseState($feedback, $helperData);
                unset($helperData);

                // Update the order status
                $patterns = array(
                    1 => __('ecpay_payment_order_comment_payment_result'),
                    2 => __('ecpay_payment_order_comment_atm'),
                    3 => __('ecpay_payment_order_comment_cvs'),
                    4 => __('ecpay_payment_order_comment_barcode'),
                );

                switch($responseStatus) {
                    case 1: // Paid
                        $status = $this->getEcpayConfig('success_status');
                        $pattern = $patterns[$responseStatus];
                        $comment = sprintf($pattern, $feedback['RtnCode'], $feedback['RtnMsg']);

                        $this->setOrderCommentForFront($order, $comment, $status, EcpayOrderModel::NOTIFY_PAYMENT_RESULT);

                        $transaction = $this->_ecpayPaymentModel->createTransaction($order, $paymentData);

                        $payment = $order->getPayment();
                        $additionalInfo = $payment->getAdditionalInformation();
                        $rawDetailsInfo = $additionalInfo["raw_details_info"];
                        $order->setData("ecpay_payment_method", $rawDetailsInfo["ecpay_choosen_payment"]);
                        $this->orderRepository->save($order);

                        $this->_ecpayPaymentModel->createInvoice($order, $transaction);

                        unset($status, $pattern, $comment);
                        break;
                    case 2: // ATM get code
                    case 3: // CVS get code
                    case 4: // Barcode get code
                        $status = $orderStatus;
                        $pattern = $patterns[$responseStatus];
                        $comment = $sdkHelper->getObtainingCodeComment($pattern, $feedback);

                        $this->setOrderCommentForFront($order, $comment, $status, EcpayOrderModel::NOTIFY_GET_CODE_RESULT);

                        $payment = $order->getPayment();
                        $additionalInfo = $payment->getAdditionalInformation();
                        $order->setData("ecpay_payment_method", $additionalInfo["ecpay_choosen_payment"]);
                        $this->orderRepository->save($order);

                        unset($status, $pattern, $comment);
                        break;
                    case 6: // Simulate Paid
                        $status = $orderStatus;
                        $comment = __('Simulate paid, update the note only.');

                        $this->setOrderCommentForBack($order, $comment, $status, EcpayOrderModel::NOTIFY_SIMULATE_PAID);

                        unset($status, $pattern, $comment);
                        break;
                    default:
                }
            }
        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            $error = $e->getMessage();
            $this->_getSession()->addError($error);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $error = $e->getMessage();
            $this->_getSession()->addError($error);
        }  catch (Exception $e) {
            $error = $e->getMessage();
        }

        if ($error !== '') {

            if (is_null($orderId) === false) {

                $status = $this->getEcpayConfig('failed_status');
                $pattern = __('ecpay_payment_order_comment_payment_failure');
                $comment = $sdkHelper->getFailedComment($pattern, $error);

                $this->setOrderCommentForFront($order, $comment, $status, EcpayOrderModel::NOTIFY_PAYMENT_RESULT);

                unset($status, $pattern, $comment);
            }

            // Set the failure result
            $resultMessage = '0|' . $error;
        }
        echo $resultMessage;
        exit;
    }

    public function isPaymentAvailable()
    {
        return $this->_ecpayPaymentModel->isPaymentAvailable();
    }

    private function setFailureStauts($comment, $order = null)
    {
        if (!is_null($order)) {
            $status = \Magento\Sales\Model\Order::STATE_CANCELED;

            $this->setOrderCommentForFront($order, $comment, $status, EcpayOrderModel::NOTIFY_CREATE_ORDER_RESULT);
        }

        return [
            'status' => 'Failure',
            'msg' => $comment
        ];
    }

    private function setOrderCommentForBack($order, $comment, $status, $notify)
    {
        $order->addStatusToHistory($status, $comment, $notify)
              ->save();
    }


    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $comment
     * @param string $status
     * @param $notify
     */
    private function setOrderCommentForFront($order, $comment, $status, $notify)
    {
        if ($status == 'holded') {
            $order->setHoldBeforeState($order->getState())->setHoldBeforeStatus($order->getStatus());
        }

        $order->setState($this->_ecpayOrderModel->getOrderState($status))
              ->setStatus($status);

        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified($notify);
        $history->setIsVisibleOnFront(true);

        $order->save();

        if ($notify === true) {
            $this->_ecpayOrderModel->emailCommentSender($order, $comment);
        }
    }

    public function getModuleVersion()
    {
        $version = $this->_moduleList->getOne('Ecpay_Ecpaypayment');
        if ($version && isset($version['setup_version'])) {
            return $version['setup_version'];
        } else {
            return null;
        }
    }

    /**
     * @param $storeId
     * @return \Magento\Framework\Phrase|string
     */
    private function getMerchandizeName($storeId)
    {
        $merchandizeName = "";

        switch ($storeId) {
            case 4:
                $merchandizeName = __('Laneige Online Shopping Center');
                break;
            case 1:
                $merchandizeName = __('Sulwhasoo Online Shopping Center');
                break;
            default :
                break;
        }
        return $merchandizeName;
    }
}
