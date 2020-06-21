<?php
/**
 * @author Eguana Team
 * @copyright Copyright (c) 2019 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: umer
 * Date: 06/17/20
 * Time: 12:00 PM
 */
namespace Eguana\StoreLocator\Api\Data;

/**
 * Interface StoreInfoInterface
 *  Eguana\StoreLocator\Api\Data
 */
interface StoreInfoInterface
{
    /*
    * constants
    */
    const ENTITY_ID = 'entity_id';
    const TITLE = 'title';
    const ADDRESS = 'address';
    const TELEPHONE = 'telephone';
    const LOCATION = 'location';
    const CREATED_AT = 'created_at';
    const STORE_ID = 'store_id';
    const AREA = 'area';
    const CITY = 'city';
    const EMAIL = 'email';
    const TIMING = 'timing';

    /*
    *  Getters
    */
    public function getEntityId();
    public function getTitle();
    public function getAddress();
    public function getTelephone();
    public function getLocation();
    public function getCreatedAt();
    public function getStoreId();
    public function getArea();
    public function getCity();
    public function getEmail();
    public function getTiming();

    /*
    *  Setters
    */
    public function setEntityId($entityId);
    public function setTitle($title);
    public function setAddress($address);
    public function setTelephone($telephone);
    public function setLocation($location);
    public function setCreatedAt($createdAt);
    public function setStoreId($storeId);
    public function setArea($area);
    public function setCity($city);
    public function setEmail($email);
    public function setTiming($timing);
}
