<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/9/20
 * Time: 7:39 AM
 */

namespace Eguana\GWLogistics\Plugin\Sales;


use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;

class OrderAddressRepositoryPlugin
{
    /**
     * @var \Eguana\GWLogistics\Api\QuoteCvsLocationRepositoryInterface
     */
    private $quoteCvsLocationRepository;

    public function __construct(\Eguana\GWLogistics\Api\QuoteCvsLocationRepositoryInterface $quoteCvsLocationRepository)
    {
        $this->quoteCvsLocationRepository = $quoteCvsLocationRepository;
    }

    /**
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $subject
     * @param $result
     * @param int $id
     */
    public function afterGet(\Magento\Sales\Api\OrderAddressRepositoryInterface $subject, $result)
    {
        if ($result->getData('cvs_location_id')) {
            $extensionAttributes = $result->getExtensionAttributes();
            try {
                $cvsLocation = $this->quoteCvsLocationRepository->getById($result->getData('cvs_location_id'));
                $extensionAttributes->setCvsLocationId($result->getData('cvs_location_id'));
                $extensionAttributes->setCvsStoreType($cvsLocation->getLogisticsSubType());
                $extensionAttributes->setCvsStoreName($cvsLocation->getCvsStoreName());
                $extensionAttributes->setCvsStoreAddress($cvsLocation->getCvsAddress());
                $extensionAttributes->setCvsStoreId($cvsLocation->getCvsStoreId());
            } catch (\Exception $e) {
                $extensionAttributes->setCvsLocationId(null);
            }
            $result->setExtensionAttributes($extensionAttributes);
        }
        return $result;
    }

    /**
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $subject
     * @param $result
     * @param SearchCriteriaInterface $searchCriteria
     */
    public function afterGetList(\Magento\Sales\Api\OrderAddressRepositoryInterface $subject, $result, SearchCriteriaInterface $searchCriteria)
    {
        foreach ($result->getItems() as $address) {
            $this->afterGet($subject, $address);
        }
        return $result;
    }
}
