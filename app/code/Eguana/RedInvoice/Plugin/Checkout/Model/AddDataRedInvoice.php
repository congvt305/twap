<?php
declare(strict_types=1);

namespace Eguana\RedInvoice\Plugin\Checkout\Model;

use Amasty\CheckoutCore\Model\Config;
use Eguana\RedInvoice\Model\RedInvoiceConfig\RedInvoiceConfig;
use Eguana\RedInvoice\Model\RedInvoiceLogger;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class AddDataRedInvoice
{
    /**
     * @var Config
     */
    private $amastyConfig;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var RedInvoiceLogger
     */
    private $redInvoiceLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RedInvoiceConfig
     */
    private $redInvoiceConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManage;

    /**
     * @param StoreManagerInterface $storeManage
     * @param RedInvoiceConfig $redInvoiceConfig
     * @param Config $amastyConfig
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param RedInvoiceLogger $redInvoiceLogger
     */
    public function __construct(
        StoreManagerInterface $storeManage,
        RedInvoiceConfig $redInvoiceConfig,
        Config $amastyConfig,
        Session $checkoutSession,
        LoggerInterface $logger,
        RedInvoiceLogger $redInvoiceLogger
    ) {
        $this->storeManage = $storeManage;
        $this->redInvoiceConfig = $redInvoiceConfig;
        $this->amastyConfig = $amastyConfig;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redInvoiceLogger = $redInvoiceLogger;
    }

    /**
     * Add red invoice in case one page checkout
     *
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return void
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $websiteId = $this->storeManage->getWebsite()->getId();
        $isModuleEnabled = $this->redInvoiceConfig->getEnableValue($websiteId);
        if ($this->amastyConfig->isEnabled() && $isModuleEnabled) {
            try {
                $extAttributes = $billingAddress->getData('extension_attributes');
                $isApply = $extAttributes->getIsApply();
                $companyName = $extAttributes->getCompanyName();
                $taxCode = $extAttributes->getTaxCode();
                $email = $extAttributes->getEmail();
                $state = $extAttributes->getState();
                $city = $extAttributes->getCity();
                $ward = $extAttributes->getWard();
                $roadName = $extAttributes->getRoadName();

                $this->checkoutSession->setIsApply($isApply);
                $this->checkoutSession->setCompanyName($companyName);
                $this->checkoutSession->setTaxCode($taxCode);
                $this->checkoutSession->setEmail($email);
                $this->checkoutSession->setState($state);
                $this->checkoutSession->setCity($city);
                $this->checkoutSession->setWard($ward);
                $this->checkoutSession->setRoadName($roadName);

                $message = 'Red invoice info after setting into checkout session';
                $redInvoiceInfo = [
                    'is_apply' => $isApply ? 'Yes' : 'No',
                    'company_name' => $companyName,
                    'tax_code' => $taxCode,
                    'email' => $email,
                    'state' => $state,
                    'city' => $city,
                    'ward' => $ward,
                    'road_name' => $roadName
                ];
                $this->redInvoiceLogger->logRedInvoiceInfo($message, $redInvoiceInfo);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
