<?php
namespace Sapt\Customer\Block;

use Amore\CustomerRegistration\Model\POSSystem;
use CJ\CustomCustomer\Helper\Data;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use CJ\CouponCustomer\Helper\Data as CouponHelper;
use Amore\PointsIntegration\Model\CustomerPointsSearch;
use Amore\PointsIntegration\Block\Points\Index as PointsIndex;

class Membership extends Template
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var CustomerPointsSearch
     */
    private $customerPointsSearch;

    /**
     * @var PointsIndex
     */
    private $pointsIndex;

    protected $_orderCustomerCollection;

    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var CouponHelper
     */
    protected $couponHelper;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Data
     */
    private $customerHelper;


    /**
     * @var POSSystem
     */
    private $posSystem;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param CouponHelper $couponHelper
     * @param Json $json
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerPointsSearch $customerPointsSearch
     * @param PointsIndex $pointsIndex
     * @param POSSystem $posSystem
     * @param Data $customerHelper
     * @param array $data
     */
    public function __construct(
        Template\Context  $context,
        CollectionFactory $orderCollectionFactory,
        Session           $customerSession,
        CouponHelper      $couponHelper,
        Json              $json,
        GroupRepositoryInterface  $groupRepository,
        CustomerPointsSearch $customerPointsSearch,
        PointsIndex $pointsIndex,
        POSSystem $posSystem,
        Data $customerHelper,
        array             $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->groupRepository = $groupRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->json = $json;
        $this->pointsIndex = $pointsIndex;
        $this->couponHelper = $couponHelper;
        $this->customerPointsSearch = $customerPointsSearch;
        $this->customerHelper = $customerHelper;
        $this->posSystem = $posSystem;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer(): \Magento\Customer\Model\Customer
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * @return int
     */
    public function getCountCoupon(): int
    {
        return $this->couponHelper->getCustomerAvailableCoupons()->getSize();
    }

    /**
     * @return int
     */
    public function getCountOrderReviewAvailable(): int
    {
        $orderCollection = $this->getOrderCollection();
        $orderCollection->addFieldToFilter('status', 'complete');
        return $orderCollection->getSize();
    }

    /**
     * @return int
     */
    public function getCountOrderReviewed(): int
    {
        $orderCollection = $this->getOrderCollection();
        return $orderCollection->getSize();
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getOrderCollection(): \Magento\Sales\Model\ResourceModel\Order\Collection
    {
        return $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('status')
            ->addFieldToFilter('customer_id', $this->getCustomer()->getId());
    }

    /**
     * @return int
     */
    public function getPoint(): int
    {
        $customer = $this->getCustomer();
        $customerPointsInfo = $this->customerPointsSearch->getMemberSearchResult($customer->getId(), $customer->getWebsiteId());
        if(!$customerPointsInfo)
        {
            return 0;
        }else{
            return (int) $customerPointsInfo['data']['availablePoint'] ?? 0;
        }
    }

/**
     * @param $groupId
     * @return GroupInterface|string
     */
    public function getGroupName($groupId)
    {
        try {
            return $this->groupRepository->getById($groupId);
        } catch (NoSuchEntityException $e) {
        } catch (LocalizedException $e) {
        }
        return '';
    }

    /**
     * @return array|mixed
     */
    public function getPointsSearchResult()
    {
        return $this->pointsIndex->getPointsSearchResult();
    }

    /**
     * @param $date
     * @return string
     */
    public function dateFormat($date)
    {
        return $this->pointsIndex->dateFormat($date);
    }

    /**
     * @return string
     */
    public function getPointsUrl()
    {
        return $this->getUrl('pointsintegration/points');
    }

    /**
     * @return string
     */
    public function getReviewUrl()
    {
        return $this->getUrl('review/customer');
    }

    public function getMembershipBenefitsUrl() {
        return $this->customerHelper->getMembershipBenefitsUrl();
    }

    /**
     * Get customer data from POS
     *
     * @return array
     */
    public function getMemberInfo() {
        $customer = $this->getCustomer();
        return $this->posSystem->getMemberInfo(
            $customer->getFirstname(),
            $customer->getLastname(),
            $customer->getMobileNumber(),
            $customer->getStoreId()
        );    }
}
