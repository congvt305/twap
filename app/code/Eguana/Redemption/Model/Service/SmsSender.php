<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: arslan
 * Date: 3/11/20
 * Time: 1:45 PM
 */

namespace Eguana\Redemption\Model\Service;

use Eguana\Redemption\Api\CounterRepositoryInterface;
use Eguana\Redemption\Model\RedemptionConfiguration\RedemptionConfiguration;
use Eguana\StoreSms\Api\SmsManagementInterface;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Eguana\StoreLocator\Api\StoreInfoRepositoryInterface;
use Magento\Email\Model\Template;
use Psr\Log\LoggerInterface;

/**
 * This class is used to send the sms to customers
 *
 * Class SmsSender
 */
class SmsSender
{
    /**#@+
     * Configuration SMS template path
     */
    const CONFIG_SMS_TEMPLATE_PATH = 'redemption/configuration/registration_sms_templates';
    /**#@-*/

    /**
     * @var SmsManagementInterface
     */
    private $smsManagement;

    /**
     * @var RedemptionConfiguration
     */
    private $redemptionConfig;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var CounterRepositoryInterface
     */
    private $counterRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StoreInfoRepositoryInterface
     */
    private $storeInfoRepository;

    /**
     * SmsSender constructor.
     *
     * @param SmsManagementInterface $smsManagement
     * @param RedemptionConfiguration $redemptionConfig
     * @param TemplateFactory $templateFactory
     * @param CounterRepositoryInterface $counterRepository
     * @param StoreManagerInterface $storeManager
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreInfoRepositoryInterface $storeInfoRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SmsManagementInterface $smsManagement,
        RedemptionConfiguration $redemptionConfig,
        TemplateFactory $templateFactory,
        CounterRepositoryInterface $counterRepository,
        StoreManagerInterface $storeManager,
        OrderAddressRepositoryInterface $orderAddressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreInfoRepositoryInterface $storeInfoRepository,
        LoggerInterface $logger
    ) {
        $this->smsManagement = $smsManagement;
        $this->redemptionConfig = $redemptionConfig;
        $this->templateFactory = $templateFactory;
        $this->counterRepository = $counterRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeInfoRepository = $storeInfoRepository;
    }

    /**
     * This method is used to send the sms when counter add by customer
     *
     * @param $counterId
     */
    public function sendSms($counterId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if (!$this->redemptionConfig->getSendSmsActive($storeId)) {
            return;
        }
        try {
            $number = $this->counterRepository->getById($counterId)->getTelephone();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return;
        }

        if ($number) {
            try {
                $message = $this->getMessage($counterId, $storeId);
                $this->smsManagement->sendMessage($number, $message, $storeId);
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e->getMessage());
            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     *
     * @param $counterId
     * @param $storeId
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\MailException
     */
    private function getMessage($counterId, $storeId)
    {
        $customer = $this->counterRepository->getById($counterId);
        $storeCounterId = $customer->getCounterId();
        $storeCounterName = $this->storeInfoRepository->getById($storeCounterId)->getTitle();
        $link = $this->getCounterLink($counterId);
        $customerName = $customer->getCustomerName();
        $templateIdentifier = $this->redemptionConfig->getMessageTemplate($storeId);
        /** @var Template $templateModel */
        $templateModel = $this->templateFactory->create();
        $params = ['customer'=> $customerName, 'counter'=> $storeCounterName, 'link'=>$link];
        $templateModel->setDesignConfig(['area' => 'frontend', 'store' => $this->storeManager->getStore()->getId()]);
        $templateModel->loadByConfigPath(self::CONFIG_SMS_TEMPLATE_PATH);

        return $templateModel->getProcessedTemplate($params);
    }

    /**
     * get counter register URL by id
     *
     * @param $counterId
     * @return string
     */
    public function getCounterLink($counterId)
    {
        $resultUrl = "";
        $token = $this->counterRepository->getById($counterId)->getToken();
        try {
            $resultUrl = $this->storeManager->getStore()
                ->getUrl('redemption/details/register', ['counter_id'=>$counterId, 'token' => $token]);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $resultUrl;
    }
}
