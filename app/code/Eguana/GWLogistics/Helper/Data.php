<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 6/20/20
 * Time: 7:51 PM
 */

namespace Eguana\GWLogistics\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const XML_PATH_ACTIVE = 'carriers/gwlogistics/active';
    const XML_PATH_TITLE = 'carriers/gwlogistics/title';
    const XML_PATH_NAME = 'carriers/gwlogistics/name';
    const XML_PATH_SHIPPING_PRICE = 'carriers/gwlogistics/shipping_price';
    const XML_PATH_SENDER_NAME = 'carriers/gwlogistics/sender_name';
    const XML_PATH_SENDER_PHONE = 'carriers/gwlogistics/sender_phone';
    const XML_PATH_SENDER_CELL_PHONE = 'carriers/gwlogistics/sender_cell_phone';
    const XML_PATH_SENDER_EMAIL = 'carriers/gwlogistics/sender_email';

    const XML_PATH_MERCHANT_ID = 'carriers/gwlogistics/merchant_id';
    const XML_PATH_PLATFORM_ID = 'carriers/gwlogistics/platform_id';
    const XML_PATH_HASH_KEY = 'carriers/gwlogistics/hash_key';
    const XML_PATH_HASH_IV = 'carriers/gwlogistics/hash_iv';

    const XML_PATH_MAP_URL = 'carriers/gwlogistics/map_url';
    const XML_PATH_CREATE_LOGISTICS_ORDER_URL = 'carriers/gwlogistics/create_logistics_order_url';
    const XML_PATH_LOGISTICS_QUERY_URL = 'carriers/gwlogistics/logistics_order_query_url';
    const XML_PATH_FAMI_REVERSE_LOGISTICS_URL = 'carriers/gwlogistics/fami_reverse_logistics_order_url';
    const XML_PATH_UNIMART_REVERSE_LOGISTICS_URL = 'carriers/gwlogistics/unimart_reverse_logistics_order_url';

    const XML_PATH_MODE = 'carriers/gwlogistics/mode';
    const XML_PATH_SEND_SMS_ACTIVE = 'carriers/gwlogistics/send_sms_active';
    const XML_PATH_MESSAGE_TEMPLATE = 'carriers/gwlogistics/message_template';

    private $productionMode;
    /**
     * @var \Eguana\GWLogistics\Model\Lib\EcpayCheckMacValue
     */
    private $ecpayCheckMacValue;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Eguana\GWLogistics\Model\Lib\EcpayCheckMacValue $ecpayCheckMacValue,
        Context $context
    ) {
        parent::__construct($context);
        $this->ecpayCheckMacValue = $ecpayCheckMacValue;
        $this->productionMode = $this->getMode();
        $this->encryptor = $encryptor;
    }

    public function getCarrierTitle($storeId = null) {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TITLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId

        );
    }
    public function getMapServerReplyUrl() {
       return $this->_getUrl('eguana_gwlogistics/SelectedCvsNotify', ['_secure' => true]);
    }

    public function getCreateShipmentReplyUrl() {
        return $this->_getUrl('eguana_gwlogistics/OrderStatusNotify', ['_secure' => true]);
    }

    public function getReverseLogisticsOrderReplyUrl() {
        return $this->_getUrl('eguana_gwlogistics/ReverseOrderStatusNotify', ['_secure' => true]);
    }

    public function getSenderName($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SENDER_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSenderPhone($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SENDER_PHONE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSenderCellPhone($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SENDER_CELL_PHONE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPlatformId($storeId = null)
    {
        $suffix = $this->productionMode === '1' ? '' : '_sandbox';
        return $this->scopeConfig->getValue(
            self::XML_PATH_PLATFORM_ID . $suffix,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getHashKey($storeId = null)
    {
        if ($this->productionMode === '1') {
            return $this->encryptor->decrypt(
                $this->scopeConfig->getValue(
                self::XML_PATH_HASH_KEY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
            ));
        }
        return $this->scopeConfig->getValue(
            self::XML_PATH_HASH_KEY . '_sandbox',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getHashIv($storeId = null)
    {
        if ($this->productionMode === '1') {
            return $this->encryptor->decrypt(
                $this->scopeConfig->getValue(
                    self::XML_PATH_HASH_IV,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ));
        }
        return $this->scopeConfig->getValue(
            self::XML_PATH_HASH_IV . '_sandbox',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMerchantId($storeId = null)
    {
        $suffix = $this->productionMode === '1' ? '' : '_sandbox';
        return $this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_ID . $suffix,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMapUrl($storeId = null)
    {
        $suffix = $this->productionMode === '1' ? '' : '_sandbox';
        return $this->scopeConfig->getValue(
            self::XML_PATH_MAP_URL . $suffix,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMode($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function validateCheckMackValue(array $params): bool
    {
        //todo: config value
        $hashKey = '';
        $hasIv = '';
        $checkMackValue = $params['CheckMacValue'];
        $checkMackValueFound = $this->ecpayCheckMacValue->Generate($params, $hashKey, $hasIv);
        return $checkMackValue === $checkMackValueFound;
    }

    public function getSendSmsActive($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SEND_SMS_ACTIVE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMessageTemplate($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MESSAGE_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

}
