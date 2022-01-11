<?php

namespace CJ\DataExport\Model\Export\Redemption;

/**
 * Class AttributeCollectionProvider
 */
class AttributeCollectionProvider
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \Magento\ImportExport\Model\Export\Factory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $collection;

    /**
     * AttributeCollectionProvider constructor.
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory
     */
    public function __construct(
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory
    ) {
        $this->collection = $collectionFactory->create(\Magento\Framework\Data\Collection::class);
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * This function gets attribute collection
     *
     * @return \Magento\Framework\Data\Collection
     * @throws \Exception
     */
    public function get(): \Magento\Framework\Data\Collection
    {
        if (count($this->collection) === 0) {
            /** @var \Magento\Eav\Model\Entity\Attribute $skuAttribute */
            $incrementIdAttribute = $this->attributeFactory->create();
            $incrementIdAttribute->setId(RedemptionInterface::REDEEM_ID);
            $incrementIdAttribute->setBackendType('varchar');
            $incrementIdAttribute->setDefaultFrontendLabel(RedemptionInterface::REDEEM_ID);
            $incrementIdAttribute->setAttributeCode(RedemptionInterface::REDEEM_ID);
            $this->collection->addItem($incrementIdAttribute);
        }
        return $this->collection;
    }
}