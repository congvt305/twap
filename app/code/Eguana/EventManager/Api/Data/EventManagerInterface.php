<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: arslan
 * Date: 29/6/20
 * Time: 3:15 PM
 */
namespace Eguana\EventManager\Api\Data;

/**
 * interface EventManagerInterface
 * @api
 */
interface EventManagerInterface
{
    const ENTITY_ID = 'entity_id';

    const TITLE = 'event_title';

    const DESCRIPTION = 'description';

    const THUMBNAIL_IMAGE = 'thumbnail_image';

    const IS_ACTIVE = 'is_active';

    const START_DATE = 'start_date';

    const END_DATE = 'end_date';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @param int $entity_id
     * @return $this
     */
    public function setEntityId($entity_id);

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * Set Thumbnail Image
     *
     * @param string $thumbnailImage
     * @return EventManagerInterface
     */

    public function setThumbnailImage($thumbnailImage);

    /**
     * Get Thumbnail Image
     *
     * @return string
     */
    public function getThumbnailImage();

    /**
     * Get Thumbnail Image
     *
     * @return string
     */
    public function getThumbnailImageURL();

    /**
     * @param string $startDate
     * @return $this
     */
    public function setStartDate($startDate);

    /**
     * @return string
     */
    public function getStartDate();

    /**
     * @param string $endDate
     * @return $this
     */
    public function setEndDate($endDate);

    /**
     * @return string
     */
    public function getEndDate();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * @return string
     */
    public function getIsActive();
}
