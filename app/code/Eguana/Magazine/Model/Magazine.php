<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: yasir
 * Date: 6/16/20
 * Time: 12:04 AM
 */
namespace Eguana\Magazine\Model;

use Eguana\Magazine\Api\Data\MagazineInterface;
use Eguana\Magazine\Model\ResourceModel\Magazine as MagazineAlias;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\UrlInterface;

/**
 * Model class to be used
 * Class Magazine
 */
class Magazine extends AbstractExtensibleModel implements MagazineInterface, IdentityInterface
{
    /**
     * @var CACHE_TAG
     */
    const CACHE_TAG = 'magazine';

    /**
     * @var STATUS_ENABLED
     */
    const STATUS_ENABLED = 1;

    /**
     * @var STATUS_DISABLED
     */
    const STATUS_DISABLED = 0;

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Empty construtor
     */
    protected function _construct()
    {
        $this->_init(MagazineAlias::class);
    }

    /**
     * Get custom attributes codes
     * @return array|string[]
     */
    public function getCustomAttributesCodes()
    {
        return [
            'entity_id',
            'title',
            'short_description',
            'content',
            'thumbnail_image',
            'thumbnail_alt',
            'type',
            'sort_order',
            'show_date',
            'created_at',
            'updated_at',
            'is_active'
        ];
    }
    /**
     * Return unique ID(s) for each object in system
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param int $entity_id
     * @return $this
     */
    public function setEntityId($entity_id)
    {
        return $this->setData(self::ENTITY_ID, $entity_id);
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @param string $shortdescription
     * @return $this
     */
    public function setShortDescription($shortDescription)
    {
        return $this->setData(self::SHORT_DESCRIPTION, $shortDescription);
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return $this->getData(self::SHORT_DESCRIPTION);
    }

    /**
     * @param $content
     * @return Magazine
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * @param string $thumbnailAlt
     * @return $this
     */
    public function setThumbnailAlt($thumbnailAlt)
    {
        return $this->setData(self::THUMBNAIL_ALT, $thumbnailAlt);
    }

    /**
     * @return string
     */
    public function getThumbnailAlt()
    {
        return $this->getData(self::THUMBNAIL_ALT);
    }

    /**
     * @param $type
     * @return Magazine
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT);
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @param string $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @return string
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * @param string $isActive
     * @return $this
     */
    public function setShowDate($showDate)
    {
        return $this->setData(self::SHOW_DATE, $showDate);
    }
    /**
     * @return string
     */
    public function getShowDate()
    {
        return $this->getData(self::SHOW_DATE);
    }
    /**
     * Receive page store ids
     *
     * @return int[]
     */
    public function getStores()
    {
        return $this->hasData('stores') ? $this->getData('stores') : $this->getData('store_id');
    }

    /**
     * Prepare block's statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }
}
