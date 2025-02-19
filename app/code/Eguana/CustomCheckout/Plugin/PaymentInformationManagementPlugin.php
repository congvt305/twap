<?php

namespace Eguana\CustomCheckout\Plugin;

use Amasty\CheckoutCore\Model\Config;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;

class PaymentInformationManagementPlugin extends \Magento\CheckoutStaging\Plugin\PaymentInformationManagementPlugin
{
    const STORE_VN_CODE = 'vn_laneige';
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var Config
     */
    private $amastyConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param VersionManager $versionManager
     * @param CartRepositoryInterface $quoteRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param Config $amastyConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        VersionManager $versionManager,
        CartRepositoryInterface $quoteRepository,
        AddressRepositoryInterface $addressRepository,
        Config $amastyConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->versionManager = $versionManager;
        $this->quoteRepository = $quoteRepository;
        $this->addressRepository = $addressRepository;
        $this->amastyConfig = $amastyConfig;
        $this->storeManager = $storeManager;
        parent::__construct($versionManager, $quoteRepository, $addressRepository);
    }

    /**
     * Disable order submitting for preview
     *
     * @param PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
                                              $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            throw new LocalizedException(__("The order can't be submitted in preview mode."));
        }

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();
        $storeCode = $this->storeManager->getStore()->getCode();
        if ($storeCode == self::STORE_VN_CODE && $this->amastyConfig->isEnabled()) {
            if (!$shippingAddress->getWard() || !$shippingAddress->getWardId() || !$shippingAddress->getCityId() ) {
                throw new StateException(__('The shipping address is missing. Please edit the address and try again.'));
            }
        }

        $shippingMethod = $shippingAddress->getShippingMethod();
        if ($shippingMethod != 'gwlogistics_CVS') {

            $quoteSameAsBilling = (int)$shippingAddress->getSameAsBilling();
            $customer = $quote->getCustomer();
            $customerId = $customer->getId();
            $hasDefaultBilling = $customer->getDefaultBilling();
            $hasDefaultShipping = $customer->getDefaultShipping();

            if ($quoteSameAsBilling === 1) {
                $sameAsBillingFlag = 1;
            } elseif (!empty($shippingAddress) && !empty($billingAddress)) {
                if ($this->amastyConfig->isEnabled()) {
                    //must remove to avoid error in function checkIfShippingNullOrNotSameAsBillingAddress
                    $billingAddress->setData('extension_attributes', null);
                }

                $sameAsBillingFlag = $quote->getCustomerId() &&
                    $this->checkIfShippingNullOrNotSameAsBillingAddress($shippingAddress, $billingAddress);
            } else {
                $sameAsBillingFlag = 0;
            }

            if ($sameAsBillingFlag) {
                $shippingAddress->setSameAsBilling(1);
                if ($customerId && !$hasDefaultBilling && !$hasDefaultShipping) {
                    $this->processCustomerShippingAddress($quote);
                }
            }
        }
    }

    /**
     * Returns true if shipping address is same as billing or it is undefined
     *
     * @param AddressInterface $shippingAddress
     * @param AddressInterface $billingAddress
     * @return bool
     */
    private function checkIfShippingNullOrNotSameAsBillingAddress(AddressInterface $shippingAddress, AddressInterface $billingAddress): bool
    {
        if($shippingAddress->getCustomerAddressId() !== null &&
            $billingAddress->getCustomerAddressId() !== null) {
            $sameAsBillingFlag = ((int)$shippingAddress->getCustomerAddressId() === (int)$billingAddress->getCustomerAddressId());
        } else {
            $quoteShippingAddressData = $shippingAddress->getData();
            $billingAddressData = $billingAddress->getData();
            if (!empty($quoteShippingAddressData) && !empty($billingAddressData)) {
                $billingData = $this->convertAddressValueToFlatArray($billingAddressData);
                $billingKeys = array_flip(array_keys($billingData));
                $shippingData = array_intersect_key($quoteShippingAddressData, $billingKeys);
                $removeKeys = ['region_code', 'save_in_address_book', 'extension_attributes'];
                $billingData = array_diff_key($billingData, array_flip($removeKeys));
                $difference = array_diff($billingData, $shippingData);
                $sameAsBillingFlag = empty($difference);
            } else {
                $sameAsBillingFlag = false;
            }
        }

        return $sameAsBillingFlag;
    }

    /**
     * Convert $address value to flat array
     *
     * @param array $address
     * @return array
     */
    private function convertAddressValueToFlatArray(array $address): array
    {
        array_walk(
            $address,
            function (&$value) {
                if (is_array($value) && isset($value['value'])) {
                    $value = (string)$value['value'];
                }
            }
        );
        return $address;
    }

    /**
     * Process customer shipping address
     *
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    private function processCustomerShippingAddress(Quote $quote): void
    {
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $customer = $quote->getCustomer();
        $hasDefaultBilling = $customer->getDefaultBilling();
        $hasDefaultShipping = $customer->getDefaultShipping();

        if ($shippingAddress->getQuoteId()) {
            $shippingAddressData = $shippingAddress->exportCustomerAddress();
        }
        if (isset($shippingAddressData)) {
            if (!$hasDefaultShipping) {
                //Make provided address as default shipping address
                $shippingAddressData->setIsDefaultShipping(true);
                if (!$hasDefaultBilling && !$billingAddress->getSaveInAddressBook()) {
                    $shippingAddressData->setIsDefaultBilling(true);
                }
            }
            //save here new customer address
            $shippingAddressData->setCustomerId($quote->getCustomerId());
            $this->addressRepository->save($shippingAddressData);
            $quote->addCustomerAddress($shippingAddressData);
            $shippingAddress->setCustomerAddressData($shippingAddressData);
            $shippingAddress->setCustomerAddressId($shippingAddressData->getId());
        }
    }

}
