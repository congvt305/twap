<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/12/20
 * Time: 9:28 AM
 */

namespace Eguana\CustomerRefund\Model;


use Eguana\CustomerRefund\Api\Data\BankInfoDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BankInfoRepository implements \Eguana\CustomerRefund\Api\BankInfoRepositoryInterface
{
    /**
     * @var \Eguana\CustomerRefund\Api\Data\BankInfoDataInterfaceFactory
     */
    private $bankInfoDataInterfaceFactory;
    /**
     * @var ResourceModel\BankInfo
     */
    private $bankInfoResource;
    /**
     * @var ResourceModel\BankInfo\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var \Eguana\CustomerRefund\Api\Data\BankInfoSearchResultInterfaceFactory
     */
    private $bankInfoSearchResultFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        \Eguana\CustomerRefund\Api\Data\BankInfoDataInterfaceFactory $bankInfoDataInterfaceFactory,
        \Eguana\CustomerRefund\Model\ResourceModel\BankInfo $bankInfoResource,
        \Eguana\CustomerRefund\Model\ResourceModel\BankInfo\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Eguana\CustomerRefund\Api\Data\BankInfoSearchResultInterfaceFactory $bankInfoSearchResultFactory
    ) {
        $this->bankInfoDataInterfaceFactory = $bankInfoDataInterfaceFactory;
        $this->bankInfoResource = $bankInfoResource;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->bankInfoSearchResultFactory = $bankInfoSearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param int $bankInfoId
     * @return BankInfoDataInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $bankInfoId): BankInfoDataInterface
    {
        $bankInfo = $this->bankInfoDataInterfaceFactory->create();
        try {
            $this->bankInfoResource->load($bankInfo, $bankInfoId);
        } catch (\Exception $e) {
            throw new NoSuchEntityException(__('Cvs location with id "%1" does not exist.', $bankInfoId));
        }

        return $bankInfo;

    }

    /**
     * @param BankInfoDataInterface $bankInfoData
     * @return BankInfoDataInterface
     * @throws CouldNotSaveException
     */
    public function save(BankInfoDataInterface $bankInfoData): BankInfoDataInterface
    {
        try {
            /** @var \Eguana\CustomerRefund\Model\BankInfo $bankInfoData */
            $this->bankInfoResource->save($bankInfoData);
            return $bankInfoData;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Unable to save the bank info.'), $e);
        }
    }

    /**
     * @param BankInfoDataInterface $bankInfoData
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(BankInfoDataInterface $bankInfoData): bool
    {
        try {
            $this->bankInfoResource->delete($bankInfoData);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Unable to delete cvs location.'), $e);
        }

        return true;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Eguana\CustomerRefund\Api\Data\BankInfoSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Eguana\CustomerRefund\Model\ResourceModel\BankInfo\Collection $collection */
        $collection = $this->collectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var \Eguana\CustomerRefund\Api\Data\BankInfoSearchResultInterface $searchResult */
        $searchResult = $this->bankInfoSearchResultFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;

    }

    /**
     * @param int $orderId
     * @return BankInfoDataInterface
     */
    public function getByOrderId(int $orderId): BankInfoDataInterface
    {
        $bankInfo = $this->bankInfoDataInterfaceFactory->create();
        $field = 'order_id';
        try {
            $this->bankInfoResource->load($bankInfo, $orderId, $field);
        } catch (\Exception $e) {
            throw new NoSuchEntityException(__('Cvs location with order id "%1" does not exist.', $orderId));
        }

        return $bankInfo;
    }
}
