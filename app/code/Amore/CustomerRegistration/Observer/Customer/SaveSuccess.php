<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: abbas
 * Date: 20. 7. 10
 * Time: 오후 5:11
 */

namespace Amore\CustomerRegistration\Observer\Customer;

use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Framework\Event\ObserverInterface;
use Amore\CustomerRegistration\Model\POSSystem;
use \Magento\Customer\Model\Data\Customer;
use Amore\CustomerRegistration\Model\Sequence;
use Amore\CustomerRegistration\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Amore\CustomerRegistration\Model\POSLogger;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\App\RequestInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResourceModel;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Amore\CustomerRegistration\Plugin\CreateCustomer;

/**
 * Call POS API on customer information change
 * Class SaveSuccess
 * @package Amore\CustomerRegistration\Observer\Customer
 */
class SaveSuccess implements ObserverInterface
{
    protected $storeManager;
    /**
     * @var \Amore\CustomerRegistration\Model\Sequence
     */
    private $sequence;

    /**
     * @var POSSystem
     */
    private $POSSystem;

    /**
     * @var Data
     */
    private $config;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var POSLogger
     */
    private $logger;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Eguana\Directory\Helper\Data
     */
    private $cityHelper;

    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var RegionFactory
     */
    private $regionFactory;
    /**
     * @var RegionResourceModel
     */
    private $regionResourceModel;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;
    /**
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var Request
     */
    private $requestApi;

    /**
     * @var \Magento\Newsletter\Model\SubscriptionManagerInterface
     */
    private $subscriptionManager;

    public function __construct(
        Request $requestApi,
        RequestInterface $request,
        RegionFactory $regionFactory,
        RegionResourceModel $regionResourceModel,
        \Eguana\Directory\Helper\Data $cityHelper,
        Sequence $sequence,
        Data $config,
        CustomerRepositoryInterface $customerRepository,
        SubscriberFactory $subscriberFactory,
        POSLogger $logger,
        POSSystem $POSSystem,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        \Magento\Framework\App\State $state,
        StoreManagerInterface $storeManager,
        \Magento\Newsletter\Model\SubscriptionManagerInterface $subscriptionManager
    ) {
        $this->requestApi = $requestApi;
        $this->sequence = $sequence;
        $this->POSSystem = $POSSystem;
        $this->subscriberFactory = $subscriberFactory;
        $this->config = $config;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->cityHelper = $cityHelper;
        $this->request = $request;
        $this->regionFactory = $regionFactory;
        $this->regionResourceModel = $regionResourceModel;
        $this->addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getArea()
    {
        return $this->state->getAreaCode();
    }

    /**
     * Observer called on successfull customer registration
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        try {
            /**
             * @var Customer $newCustomerData
             */
            $newCustomerData = $observer->getEvent()->getData('customer_data_object');

            /**
             * @var Customer $oldCustomerData
             */
            $oldCustomerData = $observer->getEvent()->getData('orig_customer_data_object');

            if ($this->isPOSRequest()) {
                $data = $this->requestApi->getRequestData();
                if ($data && isset($data['customer']['customAttributes'])) {
                    foreach ($data['customer']['customAttributes'] as $customerAttribute) {
                        if (isset($customerAttribute['attributeCode']) && $customerAttribute['attributeCode'] == 'email_subscription_status') {
                            if (isset($customerAttribute['value']) && $customerAttribute['value'] == 1) {
                                $customerStoreId = $this->getCustomerStoreId($data[CreateCustomer::SALOFFCD]);
                                $this->subscriptionManager->subscribeCustomer((int)$newCustomerData->getId(), $customerStoreId);
                            }
                        }
                    }
                }

                return true;
            }
            if ($this->getArea() === 'adminhtml' && !$oldCustomerData) {
                return;
            }

            /**
             * When customer register for the first time then old data will be null
             */
            if ($oldCustomerData == null) {
                $this->assignIntegrationNumber($newCustomerData);
                return;
            }

            $customerAddresses = $newCustomerData->getAddresses();
            if ($customerAddresses == null ||
                count($customerAddresses) == 0
            ) {
                $customerData = $this->request->getParams();
                $this->createAddressFromDMAddress($newCustomerData->getId(), $customerData);
            }

            $newDataHaveSequenceNumber = false;

            if ($newCustomerData->getCustomAttribute('integration_number') &&
                $newCustomerData->getCustomAttribute('integration_number')->getValue()
            ) {
                $newDataHaveSequenceNumber = true;
            }

            $oldDataHaveSequenceNumber = false;

            if ($oldCustomerData->getCustomAttribute('integration_number') &&
                $oldCustomerData->getCustomAttribute('integration_number')->getValue()
            ) {
                $oldDataHaveSequenceNumber = true;
            }

            if (!$oldDataHaveSequenceNumber && $newDataHaveSequenceNumber) {
                $APIParameters = $this->getAPIParameters($newCustomerData, 'register');
                $this->POSSystem->syncMember($APIParameters, $newCustomerData->getStoreId());
            } elseif ($oldDataHaveSequenceNumber && $newDataHaveSequenceNumber) {
                $oldDataAPIParameters = $this->getAPIParameters($oldCustomerData, 'update');
                $newDataAPIParameters = $this->getAPIParameters($newCustomerData, 'update');
                if ($this->APIValuesChanged($oldDataAPIParameters, $newDataAPIParameters)) {
                    $this->POSSystem->syncMember($newDataAPIParameters, $newCustomerData->getStoreId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->addExceptionMessage($e->getMessage());
        }
    }



    /**
     * check POS request
     *
     * @return bool
     */
    private function isPOSRequest()
    {
        $data = $this->requestApi->getRequestData();
        if ($data && isset($data[CreateCustomer::IS_POS]) && $data[CreateCustomer::IS_POS] == 1) {
            return true;
        }
        return false;
    }

    /**
     * Assign integration number to the customer
     * @param $customer Customer
     * @return mixed
     */
    private function assignIntegrationNumber($customer)
    {
        try {
            if ($customer->getCustomAttribute('ba_code')) {
                $baCode = $this->POSSystem->checkBACodePrefix(
                    $customer->getCustomAttribute('ba_code')->getValue()
                );
                $customer->setCustomAttribute('ba_code', $baCode);
            }

            $posOrOnline = 'online';
            /**
             * @Abbas on the request of client. Now if customer register using bar code even than he can be online or
             * offline. So if POS have customer information then he will be considered as offline else online
             */
            /*if ($customer->getCustomAttribute('referrer_code')) {
                $posOrOnline = 'pos';
            }*/
            if ($posOrOnline == 'online') {
                $posOrOnline = $customer->getCustomAttribute('imported_from_pos')->getValue() == 1 ? 'pos' : 'online';
            }

            $this->sequence->setCustomerType($posOrOnline);
            if ($posOrOnline == 'online') {
                $this->sequence->setCustomerWebsiteid($customer->getWebsiteId());
                $secquenceNumber = $this->sequence->getNextValue();
                $customer->setCustomAttribute('integration_number', $secquenceNumber);
            }
            $customer->setCustomAttribute(
                'sales_organization_code',
                $this->config->getOrganizationSalesCode($customer->getWebsiteId())
            );
            $customer->setCustomAttribute(
                'sales_office_code',
                $this->config->getOfficeSalesCode($customer->getWebsiteId())
            );
            //$customer->setCustomAttribute('partner_id', $this->config->getPartnerId($customer->getWebsiteId()));
            return $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            $this->logger->addExceptionMessage($e->getMessage());
            return $customer;
        }
    }

    /**
     * To get POS API parameters
     * @param Customer $customer
     * @param $action
     * @return array
     */
    private function getAPIParameters($customer, $action)
    {
        try {
            $parameters = [];
            $parameters['cstmIntgSeq'] = $customer->getCustomAttribute('integration_number')->getValue();
            $parameters['if_flag'] = $action == 'register' ? 'I' : 'U';
            $parameters['firstName'] = trim($customer->getFirstname());
            $parameters['lastName'] = trim($customer->getLastname());
            $parameters['birthDay'] = $customer->getDob() ? preg_replace('/\D/', '', $customer->getDob()) : '';
            $parameters['mobileNo'] = $customer->getCustomAttribute('mobile_number')->getValue();
            $parameters['email'] = trim($customer->getEmail());
            $parameters['sex'] = $customer->getGender() == '1' ? 'M' : 'F';
            $parameters['emailYN'] = $this->isCustomerSubscribToNewsLetters($customer->getId()) ? 'Y' : 'N';
            if ($customer->getCustomAttribute('ba_code')) {
                $baCode = $this->POSSystem->checkBACodePrefix(
                    $customer->getCustomAttribute('ba_code')->getValue()
                );
            $parameters['empID'] = $baCode;
        } else {
            $parameters['empID'] = '';
        }
        if ($customer->getCustomAttribute('call_subscription_status')) {
            $parameters['callYN'] = $customer->getCustomAttribute('call_subscription_status')->getValue() == 1 ? 'Y' : 'N';
        } else {
            $parameters['callYN'] = 'N';
        }
        $parameters['smsYN'] = $customer->getCustomAttribute('sms_subscription_status') && $customer->getCustomAttribute('sms_subscription_status')->getValue() == 1 ? 'Y' : 'N';
        if ($customer->getCustomAttribute('dm_subscription_status')) {
            $parameters['dmYN'] = $customer->getCustomAttribute('dm_subscription_status')->getValue() == 1 ? 'Y' : 'N';
        } else {
            $parameters['dmYN'] = '';
        }
        $defaultBillingAddressId = $customer->getDefaultBilling();
        $customerData = $this->request->getParams();
            if (isset($customerData['dm_zipcode']) && !$defaultBillingAddressId) {
                $parameters['homeAddr1'] = $customerData['dm_detailed_address'];
                $parameters['homeZip'] = $customerData['dm_zipcode'];
                $regionName = $customerData['dm_state'];
                $regionObject = null;
                if ($regionName) {
                    $regionObject = $this->getRegionObject($regionName);
                    $parameters['homeCity'] = $regionObject->getCode() ? $regionObject->getCode() : '';
                } else {
                    $parameters['homeCity'] = '';
                }

                $cityName = $customerData['dm_city'];
                $parameters['homeState'] = '';
                if ($cityName && $regionObject) {
                    $cities = $this->cityHelper->getCityData();
                    $regionCities = $cities[$regionObject->getRegionId()];
                    foreach ($regionCities as $regionCity) {
                        if ($regionCity['name'] == $cityName) {
                            $parameters['homeState'] = $regionCity['pos_code'];
                            break;
                        }
                    }
                }
            } elseif ($defaultBillingAddressId) {
                $addresses = $customer->getAddresses();
                $defaultBillingAddress = null;
                foreach ($addresses as $address) {
                    if ($address->getId() == $defaultBillingAddressId) {
                        $defaultBillingAddress = $address;
                        break;
                    }
                }
                $addressParameters = $this->getAddressParameters($defaultBillingAddress);
                $parameters = array_merge($parameters, $addressParameters);
            }
            $parameters['salOrgCd'] = $customer->getCustomAttribute('sales_organization_code') ?
                $customer->getCustomAttribute('sales_organization_code')->getValue() : '';
            $parameters['salOffCd'] = $customer->getCustomAttribute('sales_office_code') ?
                $customer->getCustomAttribute('sales_office_code')->getValue() : '';
            $parameters['prtnrid'] = $customer->getCustomAttribute('partner_id') ?
                $customer->getCustomAttribute('partner_id')->getValue() : '';
            $parameters['statusCD'] = '01';
            $store = $this->storeManager->getStore($customer->getStoreId());
            if ($store->getCode() == 'my_laneige') {
                $parameters['race'] = $customer->getCustomAttribute('race') ? $customer->getCustomAttribute('race')->getValue() : '';
            }

            return $parameters;
        } catch (\Exception $exception) {
            $this->logger->addExceptionMessage($exception->getMessage());
            $this->logger->addExceptionMessage('Fail to get API Parameter: ' . json_encode($parameters) . 'Customer Data: ' . json_encode($customerData));
        }
    }

    private function getRegionObject($regionName)
    {
        /** @var \Magento\Directory\Model\Region $region */
        $region = $this->regionFactory->create();
        try {
            $this->regionResourceModel->load($region, $regionName, 'default_name');
        } catch (\Exception $e) {
            $this->logger->addExceptionMessage($e->getMessage());
        }
        return $region;
    }

    /**
     * To get address values
     * From default billing address get the parameters for the POS sync API
     * @param \Magento\Customer\Model\Data\Address $address
     * @return array
     */
    private function getAddressParameters($address)
    {
        $parameters = [];
        $parameters['homeCity'] = $address->getRegion()->getRegionCode();
        $parameters['homeAddr1'] = implode(' ', $address->getStreet());
        $parameters['homeZip'] = $address->getPostcode();
        $cityName = $address->getCity();
        $parameters['homeState'] = '';
        if ($cityName) {
            $cities = $this->cityHelper->getCityData();
            $regionCities = $cities[$address->getRegionId()];
            foreach ($regionCities as $regionCity) {
                if ($regionCity['name'] == $cityName) {
                    $parameters['homeState'] = $regionCity['pos_code'];
                    break;
                }
            }
        }
        return $parameters;
    }

    private function APIValuesChanged($oldValues, $newValues)
    {
        $result = false;
        foreach ($oldValues as $key => $value) {
            if ($value != $newValues[$key]) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * To check whether customer is subscribed to the news letters or not
     *
     * @param int $customerId
     *
     * @return bool
     */
    private function isCustomerSubscribToNewsLetters($customerId)
    {
        /**
         * @var Subscriber $subscriber
         */
        $subscriber = $this->subscriberFactory->create();
        $status = $subscriber->loadByCustomerId((int)$customerId)->isSubscribed();

        return (bool)$status;
    }

    /**
     * Create address from dm address
     * @param Customer $customer
     */
    private function createAddressFromDMAddress($customerId, $customerData)
    {
        try {
            $status = isset($customerData['dm_subscription_status_checkbox'])?
                $customerData['dm_subscription_status_checkbox']:'';
            if (isset($customerData['dm_zipcode'])) {
                $dmCity = $customerData['dm_city'];
                $cityId = isset($customerData['city_id']) ? $customerData['city_id'] :'';
                $ward = isset($customerData['ward']) ? $customerData['ward'] : '';
                $wardId = isset($customerData['ward_id']) ? $customerData['ward_id'] : '';
                $dmZipCode = $customerData['dm_zipcode'];
                $dmDetailedAddress = $customerData['dm_detailed_address'];
                $dmState = $customerData['dm_state'];
                $dmCountryId = $customerData['country_id'];
                $regionId = isset($customerData['region_id']) ? $customerData['region_id'] : "";
                $firstName = $customerData['firstname'];
                $lastName = $customerData['lastname'];
                $mobileNumber = $customerData['mobile_number'];
                /** @var \Magento\Customer\Api\Data\AddressInterface $addressData */
                $defaultShippingAddressData = $this->addressDataFactory->create();
                $defaultShippingAddressData->setCustomerId($customerId)
                    ->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setRegionId($regionId)
                    ->setPostcode($dmZipCode)
                    ->setCountryId($dmCountryId)
                    ->setCity($dmCity)
                    ->setTelephone($mobileNumber)
                    ->setStreet([$dmDetailedAddress])
                    ->setIsDefaultShipping('1');
                if ($cityId && $ward && $wardId) {
                    $defaultShippingAddressData->setCustomAttribute('city_id',$cityId)
                        ->setCustomAttribute('ward', $ward)
                        ->setCustomAttribute('ward_id',$wardId);
                }
                $this->addressRepository->save($defaultShippingAddressData);
                /** @var \Magento\Customer\Api\Data\AddressInterface $addressData */
                $defaultBillingAddressData = $this->addressDataFactory->create();
                $defaultBillingAddressData->setCustomerId($customerId)
                    ->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setRegionId($regionId)
                    ->setPostcode($dmZipCode)
                    ->setCountryId($dmCountryId)
                    ->setCity($dmCity)
                    ->setTelephone($mobileNumber)
                    ->setStreet([$dmDetailedAddress])
                    ->setIsDefaultBilling('1');
                if ($cityId && $ward && $wardId) {
                    $defaultBillingAddressData->setCustomAttribute('city_id',$cityId)
                        ->setCustomAttribute('ward', $ward)
                        ->setCustomAttribute('ward_id',$wardId);
                }
                $this->addressRepository->save($defaultBillingAddressData);
            }
        } catch (\Exception $e) {
            $this->logger->addExceptionMessage($e->getMessage());
        }
    }

    private function getCustomerStoreId($salOffCd)
    {
        $customerStoreId = 0;
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            if ($storeId) {
                $officeSaleCode = $this->config->getOfficeSalesCode($storeId);
                if ($officeSaleCode == $salOffCd) {
                    $customerStoreId = $storeId;
                    break;
                }
            }
        }
        return $customerStoreId;
    }
}
