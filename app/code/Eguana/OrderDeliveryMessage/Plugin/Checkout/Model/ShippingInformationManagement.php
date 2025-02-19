<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created byPhpStorm
 * User:  Abbas
 * Date: 6/30/20
 * Time: 10:30 am
 */

namespace Eguana\OrderDeliveryMessage\Plugin\Checkout\Model;

use Amasty\Promo\Model\Storage;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Escaper;

/**
 * Get Delivery Message in checkout process
 *
 * Class ShippingInformationManagement
 */
class ShippingInformationManagement
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var Storage
     */
    private $registry;

    /**
     * @param QuoteRepository $quoteRepository
     * @param Escaper $escaper
     * @param Storage $registry
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        Escaper $escaper,
        Storage $registry
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->escaper = $escaper;
        $this->registry = $registry;
    }

    /**
     * SHORT DESCRIPTION
     * LONG DESCRIPTION LINE BY LINE
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
        if (!isset($extAttributes)) {
            return;
        }
        $deliveryMessage = $extAttributes->getDeliveryMessage();
        /**
         * By Abbas I am using strip_tags because I did not get any related function in Magento 2
         * In core files they are also using it. For example at
         * vendor/magento/module-catalog/Model/Product/Option/Type/File.php Line 407
         */
        $deliveryMessage = $deliveryMessage ? $this->escaper->escapeHtml(strip_tags($deliveryMessage)) : $deliveryMessage;
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setDeliveryMessage($deliveryMessage);
        //prevent auto add product when collect total quote
        $this->registry->setIsAutoAddAllowed(false);
        $this->quoteRepository->save($quote);

    }

}
