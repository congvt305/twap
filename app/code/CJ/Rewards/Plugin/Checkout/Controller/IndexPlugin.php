<?php
declare(strict_types=1);

namespace CJ\Rewards\Plugin\Checkout\Controller;

use CJ\Rewards\Model\Data;
use CJ\Rewards\Model\ReCheckAndUpdatePoint;
use Magento\Checkout\Model\Session;

class IndexPlugin
{
    const TW_STORE_CODE = [
      'tw_laneige',
      'default'
    ];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var Data
     */
    private $rewardsData;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ReCheckAndUpdatePoint
     */
    private $reCheckAndUpdatePoint;

    /**
     * @param \Magento\Customer\Model\Session $_customerSession
     * @param Data $rewardsData
     * @param Session $checkoutSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $_customerSession,
        Data $rewardsData,
        Session $checkoutSession,
        ReCheckAndUpdatePoint $reCheckAndUpdatePoint
    ) {
        $this->_customerSession = $_customerSession;
        $this->rewardsData = $rewardsData;
        $this->checkoutSession = $checkoutSession;
        $this->reCheckAndUpdatePoint = $reCheckAndUpdatePoint;
    }
    /**
     * Update point when go to checkout cart
     *
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\Index $subject)
    {
        $quote = $this->checkoutSession->getQuote();
        if (!in_array($quote->getStore()->getCode(), self::TW_STORE_CODE)) {
            return;
        }
        if ($this->_customerSession->isLoggedIn() && $this->rewardsData->canUseRewardPoint($quote)) {
            $customer = $this->_customerSession->getCustomer();
            $this->reCheckAndUpdatePoint->update($customer);
        }
    }
}
