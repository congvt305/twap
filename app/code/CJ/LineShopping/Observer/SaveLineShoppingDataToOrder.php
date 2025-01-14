<?php
namespace CJ\LineShopping\Observer;

use CJ\LineShopping\Helper\Config;
use Exception;
use CJ\LineShopping\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use CJ\LineShopping\Helper\CookieLineInformation;

class SaveLineShoppingDataToOrder implements ObserverInterface
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var CookieLineInformation
     */
    protected CookieLineInformation $cookieLineInformation;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param Logger $logger
     * @param Json $json
     * @param CookieLineInformation $cookieLineInformation
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        Json $json,
        CookieLineInformation $cookieLineInformation,
        Config $config
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->cookieLineInformation = $cookieLineInformation;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(
        Observer $observer
    ) {
        try {
            $order = $observer->getEvent()->getOrder();
            $websiteId = $order->getStore()->getWebsiteId();

            $enableSaveUtm = $this->config->isEnableSaveUtm($websiteId);
            $lineInfo = $this->cookieLineInformation->getCookie(CookieLineInformation::UTM_INFORMATION_COOKIE_NAME);
            if($enableSaveUtm && $lineInfo) {
                $data = $this->json->unserialize($lineInfo);
                foreach (CookieLineInformation::UTM_INFO_LIST as $item) {
                    if (isset($data[$item])) {
                        $order->setData('line_' . $item, $data[$item]);
                    }
                }
                $this->cookieLineInformation->removeCookie(CookieLineInformation::UTM_INFORMATION_COOKIE_NAME);
            }
            $enable = $this->config->isEnable($websiteId);
            $lineEcid = $this->cookieLineInformation->getCookie(CookieLineInformation::LINE_SHOPPING_ECID_COOKIE_NAME);
            if (!$lineEcid || !$enable) {
                return $this;
            }

            $order->setData('line_ecid', $lineEcid);
            $order->setData('is_line_shopping', 1);

            return $this;
        } catch (Exception $exception) {
            $this->logger->error(Logger::ORDER_POST_BACK,
                [
                    'message' => $exception->getMessage()
                ]
            );
            return $this;
        }
    }
}
