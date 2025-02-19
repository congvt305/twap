<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/27/20
 * Time: 2:20 PM
 */

namespace Eguana\BlackCat\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
class HomeDelivery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const XML_PATH_SHIPPING_PRICE = 'carriers/blackcat/shipping_price';
    const XML_PATH_TITLE = 'carriers/blackcat/title';
    /**
     * @var string
     */
    protected $_code = 'blackcat';
    /**
     * Rate result data
     *
     * @var Result
     */
    private $result;
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;
    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    private $trackFactory;
    /**
     * @var \Magento\Shipping\Model\Tracking\Result\ErrorFactory
     */
    private $trackErrorFactory;
    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    private $trackStatusFactory;

    public function __construct(
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->trackFactory = $trackFactory;
        $this->trackErrorFactory = $trackErrorFactory;
        $this->trackStatusFactory = $trackStatusFactory;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $freeBoxes = $this->getFreeBoxesCount($request);
        $this->setFreeBoxes($freeBoxes);

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        $shippingPrice = $this->getShippingPrice($request, $freeBoxes);

        if ($shippingPrice !== false) {
            $method = $this->createResultMethod($shippingPrice);
            $result->append($method);
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return [
            $this->_code => $this->getConfigData('title')
        ];
    }

    /**
     * Creates result method
     *
     * @param int|float $shippingPrice
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    private function createResultMethod($shippingPrice)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier('blackcat');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('homedelivery');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        return $method;
    }

    private function getShippingPrice(RateRequest $request, $freeBoxes)
    {
        $shippingPrice = $this->getConfigData('shipping_price');
        if ($shippingPrice !== false && $request->getPackageQty() == $freeBoxes) {
            $shippingPrice = '0.00';
        }
        return $shippingPrice;
    }

    /**
     * Get count of free boxes
     *
     * @param RateRequest $request
     * @return int
     */
    private function getFreeBoxesCount(RateRequest $request)
    {
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        return $freeBoxes;
    }

    /**
     * Returns free boxes count of children
     *
     * @param mixed $item
     * @return mixed
     */
    private function getFreeBoxesCountFromChildren($item)
    {
        $freeBoxes = 0;
        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }
        return $freeBoxes;
    }

    /**
     * Get tracking information
     *
     * @param string $tracking
     * @return string|false
     * @api
     */
    public function getTrackingInfo($tracking) //$tracking is the tracking number, no need to implement, remove later
    {
        $result = $this->getTracking($tracking);

        if ($result instanceof \Magento\Shipping\Model\Tracking\Result) {
            $trackings = $result->getAllTrackings();
            if ($trackings) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result
     */
    public function getTracking($trackings)
    {
//        $this->setTrackingReqeust(); //todo: set merchant ID later
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }
        foreach ($trackings as $tracking) {
            $this->getBlackCatTracking($tracking);
        }
        return $this->result;
    }

    private function getBlackCatTracking($trackingValue)
    {
        if (!$this->result) {
            $this->result = $this->trackFactory->create();
        }
        $tracking = $this->trackStatusFactory->create();
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle($this->getConfigData('title'));
        $tracking->setTracking($trackingValue);
        $tracking->setPopup(1);
        $tracking->setUrl(
            "https://www.t-cat.com.tw/inquire/Trace.aspx?no={$trackingValue}"
        );
        $this->result->append($tracking);
    }

    public function isTrackingAvailable()
    {
        return true;
    }

}
