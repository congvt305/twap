<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: arslan
 * Date: 11/9/20
 * Time: 4:43 PM
 */
namespace Eguana\NameSorter\Model\Rma\Status;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory as ExtensionAttributesFactoryAlias;
use Magento\Framework\App\Area as AreaAlias;
use Magento\Framework\Data\Collection\AbstractDb as AbstractDbAlias;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder as TransportBuilderAlias;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource as AbstractResourceAlias;
use Magento\Framework\Registry as RegistryAlias;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeAlias;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface as StateInterfaceAlias;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Helper\Data as DataAlias;
use Magento\Rma\Model\Config as ConfigAlias;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\History as HistoryAlias;
use Magento\Rma\Model\RmaFactory as RmaFactoryAlias;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Store\Model\Store as StoreAlias;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterfaceAlias;
use Magento\Framework\App\Request\Http;

/**
 * This class is used for the preference of History Model which
 * Swap the First and Last Name in comment email
 *
 * Class History
 */
class History extends HistoryAlias
{
    /**
     * @var Http
     */
    private $request;

    /**
     * Rma configuration
     *
     * @var ConfigAlias
     */
    private $rmaConfig;

    /**
     * @var StateInterfaceAlias
     */
    protected $inlineTranslation;

    /**
     * Core store manager interface
     *
     * @var StoreManagerInterfaceAlias
     */
    private $storeManager;

    /**
     * Mail transport builder
     *
     * @var TransportBuilderAlias
     */
    private $transportBuilder;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var DataAlias
     */
    protected $rmaHelper;

    /**
     * History constructor.
     * @param Context $context
     * @param RegistryAlias $registry
     * @param ExtensionAttributesFactoryAlias $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param StoreManagerInterfaceAlias $storeManager
     * @param RmaFactoryAlias $rmaFactory
     * @param ConfigAlias $rmaConfig
     * @param TransportBuilderAlias $transportBuilder
     * @param DateTimeAlias $dateTimeDateTime
     * @param StateInterfaceAlias $inlineTranslation
     * @param DataAlias $rmaHelper
     * @param TimezoneInterface $localeDate
     * @param RmaRepositoryInterface $rmaRepositoryInterface
     * @param RmaAttributesManagementInterface $metadataService
     * @param AddressRenderer $addressRenderer
     * @param AbstractResourceAlias|null $resource
     * @param AbstractDbAlias|null $resourceCollection
     * @param Http $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        RegistryAlias $registry,
        ExtensionAttributesFactoryAlias $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        StoreManagerInterfaceAlias $storeManager,
        RmaFactoryAlias $rmaFactory,
        ConfigAlias $rmaConfig,
        TransportBuilderAlias $transportBuilder,
        DateTimeAlias $dateTimeDateTime,
        StateInterfaceAlias $inlineTranslation,
        DataAlias $rmaHelper,
        TimezoneInterface $localeDate,
        RmaRepositoryInterface $rmaRepositoryInterface,
        RmaAttributesManagementInterface $metadataService,
        AddressRenderer $addressRenderer,
        Http $request,
        AbstractResourceAlias $resource = null,
        AbstractDbAlias $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $rmaFactory,
            $rmaConfig,
            $transportBuilder,
            $dateTimeDateTime,
            $inlineTranslation,
            $rmaHelper,
            $localeDate,
            $rmaRepositoryInterface,
            $metadataService,
            $addressRenderer,
            $resource,
            $resourceCollection,
            $data
        );
        $this->rmaConfig = $rmaConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->addressRenderer = $addressRenderer;
        $this->rmaHelper = $rmaHelper;
        $this->request = $request;
    }

    /**
     * Sending email to admin with customer's comment data
     *
     * @param string $rootConfig Current config root
     * @param array $sendTo mail recipient array
     * @param bool $isGuestAvailable
     * @param string $templateArea
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _sendCommentEmail(
        $rootConfig,
        $sendTo,
        $isGuestAvailable = true,
        $templateArea = AreaAlias::AREA_FRONTEND
    ) {
        $rma = $this->getRma();

        $this->rmaConfig->init($rootConfig, $rma->getStoreId());
        if (!$this->rmaConfig->isEnabled()) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $copyTo = $this->rmaConfig->getCopyTo();
        $copyMethod = $this->rmaConfig->getCopyMethod();

        if ($isGuestAvailable && $rma->getOrder()->getCustomerIsGuest()) {
            $template = $this->rmaConfig->getGuestTemplate();
        } else {
            $template = $this->rmaConfig->getTemplate();
        }

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }

        if ($templateArea == AreaAlias::AREA_FRONTEND) {
            $storeId = $rma->getStoreId();
        } else {
            $storeId = StoreAlias::DEFAULT_STORE_ID;
        }
        $store = $this->storeManager->getStore($storeId);
        $identity = $this->rmaConfig->getIdentity('', $storeId);

        if ($isGuestAvailable && $rma->getOrder()->getCustomerIsGuest()) {
            $customerName = $rma->getOrder()->getCustomerLastname() . ' ' . $rma->getOrder()->getCustomerFirstname();
        } else {
            $customerName = $rma->getOrder()->getCustomerName();
        }

        foreach ($sendTo as $recipient) {
            $transport = $this->transportBuilder->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    ['area' => $templateArea, 'store' => $storeId]
                )
                ->setTemplateVars(
                    [
                        'rma' => $rma,
                        'rma_data' => [
                            'status_label' => $rma->getStatusLabel(),
                        ],
                        'order' => $rma->getOrder(),
                        'order_data' => [
                            'customer_name' => $customerName,
                        ],
                        'comment' => $this->getComment(),
                        'supportEmail' => $store->getConfig('trans_email/ident_support/email'),
                    ]
                )
                ->setFromByScope($identity, $storeId)
                ->addTo($recipient['email'], $recipient['name'])
                ->addBcc($bcc)
                ->getTransport();

            $transport->sendMessage();
        }
        $this->setEmailSent(true);
        $this->inlineTranslation->resume();
        return $this;
    }

    /**
     * Sending authorizing email with RMA data
     *
     * @param Rma $rma
     * @param string $rootConfig
     * @return HistoryAlias
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _sendRmaEmailWithItems(Rma $rma, $rootConfig)
    {
        $storeId = $rma->getStoreId();
        $order = $rma->getOrder();

        $this->rmaConfig->init($rootConfig, $storeId);
        if (!$this->rmaConfig->isEnabled()) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $copyTo = $this->rmaConfig->getCopyTo();
        $copyMethod = $this->rmaConfig->getCopyMethod();

        if ($order->getCustomerIsGuest()) {
            $template = $this->rmaConfig->getGuestTemplate();
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $template = $this->rmaConfig->getTemplate();
            $customerName = $rma->getCustomerName();
        }

        $sendTo = [['email' => $order->getCustomerEmail(), 'name' => $customerName]];
        if ($rma->getCustomerCustomEmail() && $rma->getCustomerCustomEmail() !== $order->getCustomerEmail()) {
            $sendTo[] = ['email' => $rma->getCustomerCustomEmail(), 'name' => $customerName];
        }
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        $returnAddress = $this->rmaHelper->getReturnAddress('html', [], $storeId);

        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }
        $store = $this->storeManager->getStore($storeId);
        $identity = $this->rmaConfig->getIdentity('', $storeId);

        $fullActionName   = $this->request->getFullActionName();
        if (($order->getCustomerIsGuest()) && ($fullActionName == 'magento_rma_guest_submit')) {
            $customerNameOnEmail = $order->getCustomerName();
        } else {
            $customerNameOnEmail = $order->getCustomerLastname() . ' ' . $order->getCustomerFirstname();
        }
        try {
            foreach ($sendTo as $recipient) {
                $transport = $this->transportBuilder->setTemplateIdentifier($template)
                    ->setTemplateOptions(['area' => AreaAlias::AREA_FRONTEND, 'store' => $storeId])
                    ->setTemplateVars(
                        [
                            'rma' => $rma,
                            'rma_data' => [
                                'status_label' => $rma->getStatusLabel(),
                            ],
                            'order' => $order,
                            'order_data' => [
                                'customer_name' => $customerNameOnEmail,
                            ],
                            'created_at_formatted_1' => $rma->getCreatedAtFormated(1),
                            'store' => $store,
                            'return_address' => $returnAddress,
                            'item_collection' => $rma->getItemsForDisplay(),
                            'formattedShippingAddress' => $this->addressRenderer->format(
                                $order->getShippingAddress(),
                                'html'
                            ),
                            'formattedBillingAddress' => $this->addressRenderer->format(
                                $order->getBillingAddress(),
                                'html'
                            ),
                            'supportEmail' => $store->getConfig('trans_email/ident_support/email'),
                            'storePhone' => $store->getConfig('general/store_information/phone'),
                        ]
                    )
                    ->setFromByScope($identity, $storeId)
                    ->addTo($recipient['email'], $recipient['name'])
                    ->addBcc($bcc)
                    ->getTransport();

                $transport->sendMessage();
            }
            $this->setEmailSent(true);
        } catch (MailException $exception) {
            $this->_logger->critical($exception->getMessage());
        }
        $this->inlineTranslation->resume();
        return $this;
    }
}
