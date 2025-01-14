<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/27/20
 * Time: 3:47 PM
 */

namespace Eguana\StoreSms\Model;


class SmsManagement implements \Eguana\StoreSms\Api\SmsManagementInterface
{
    /**
     * @var \Eguana\StoreSms\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @var \Eguana\StoreSms\Logger\Logger 
     */
    protected $logger;

    public function __construct(
        \Eguana\StoreSms\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Eguana\StoreSms\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * @param string $number
     * @param string $message
     * @param int|null $storeId
     * @return bool
     */
    public function sendMessage(string $number, string $message, int $storeId = null): bool
    {
        $result = true;
        try {
            if ($storeId == null) {
                $storeId = $this->storeManager->getStore()->getId();
            }

            $numberPrefix = $this->helper->getCountryCode($storeId);
            $phoneNumber = $this->getPhoneNumberWithCode($number, $numberPrefix);
            $apiUserName = $this->helper->getApiCredentials('api_login', $storeId);
            $apiPassword = $this->helper->getApiCredentials('api_password', $storeId);
            $sender = $this->helper->getSender($storeId);
            $apiUrl = $this->helper->getApiCredentials('api_url', $storeId);
            $authorizationKey = "Basic " . base64_encode($apiUserName . ":" . $apiPassword);
            $header = [
                "accept: application/json",
                "authorization: " . $authorizationKey,
                "content-type: application/json"
            ];
            $param = [
                'from' => $sender,
                'to' => $phoneNumber,
                'text' => $message
            ];
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_HTTPHEADER, $header);
            $this->curl->setOption(CURLOPT_HEADER, false);
            $this->curl->setOption(CURLOPT_POST, true);
            $this->curl->setOption(CURLOPT_POSTFIELDS, json_encode($param));
            $this->logger->info("====START SENDING SMS REDEMPTION====");
            $this->logger->info(__("Send request: %1", json_encode($param)));
            $this->curl->post($apiUrl, $param);
            $status = $this->curl->getStatus();
            $this->logger->info(__("Response for " . $number . ":%1", $this->curl->getBody()));

            if ($status != 200) {
                $result = false;
            }

        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    private function getPhoneNumberWithCode($number, $countryCode)
    {
        $phoneNumber = '';
        $n = strlen($countryCode);
        $result = substr($number, 0, $n);
        if ($result == $countryCode) {
            $phoneNumber = $number;
        } elseif ($number[0] == 0) {
            $phoneWithLeadingZero = ltrim($number, '0');
            $phoneNumber = $countryCode . $phoneWithLeadingZero;
        } else {
            $phoneNumber = $countryCode . $number;
        }

        return $phoneNumber;

    }

}
