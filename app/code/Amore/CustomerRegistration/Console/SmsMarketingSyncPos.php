<?php

namespace Amore\CustomerRegistration\Console;

use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\State;

class SmsMarketingSyncPos extends \Symfony\Component\Console\Command\Command
{
    const NAME = 'pos:smsmarketing:sync';
    const TIME_TO_MIGRATE = '2022-03-24';
    const STORE_CODE_APPLY = ['default', 'tw_laneige'];
    const QUANTITY = 'qty';
    const QUANTITY_DEFAULT = 100;
    const SMS_SUBSCRIPTION_STATUS = 'sms_subscription_status';
    const ENABLE_VALUE = 1;


    /**
     * @var CustomerCollectionFactory
     */
    protected CustomerCollectionFactory $customerCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var AddressRegistry|mixed
     */
    protected AddressRegistry $addressRegistry;

    /**
     * @var CustomerRepository
     */
    protected CustomerRepository $customerRepository;

    /**
     * @var State
     */
    protected State $state;

    /**
     * @param State $state
     * @param CustomerRepository $customerRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param AddressRegistry $addressRegistry
     * @param string|null $name
     */
    public function __construct(
        State $state,
        CustomerRepository $customerRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        StoreManagerInterface $storeManager,
        AddressRegistry $addressRegistry,
        string $name = null
    ) {
        $this->state = $state;
        $this->customerRepository = $customerRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->addressRegistry = $addressRegistry;
        parent::__construct($name);
    }
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('POS - EC SMS Marketing synchronization');
        $this->addOption(
            self::QUANTITY,
            null,
            InputOption::VALUE_OPTIONAL, 'Qty to run'
        );
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $quantity = $input->getOption(self::QUANTITY) ? $input->getOption(self::QUANTITY) : self::QUANTITY_DEFAULT;

        $customers = $this->customerCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('created_at', ['lteq' => self::TIME_TO_MIGRATE])
            ->addFieldToFilter('updated_at', ['lteq' => self::TIME_TO_MIGRATE])
            ->addFieldToFilter('store_id', ['in' => $this->getWebsiteApply()])
            ->addAttributeToFilter('call_subscription_status', 1);
        $customers
            ->setPageSize($quantity)
            ->setCurPage(1)
            ->load();
        $output->writeln("Customers' SMS marketing synchronization has started");
        $output->writeln("Check more detail of progress at this file var/log/pos.log");
        foreach ($customers as $customer) {
            try {
                $this->disableAddressValidation($customer);
                $customerRes = $this->customerRepository->getById($customer->getId());
                $customerRes->setCustomAttribute(self::SMS_SUBSCRIPTION_STATUS, self::ENABLE_VALUE);
                $this->customerRepository->save($customerRes);
                $output->writeln("Done sync customer with ID: " . $customer->getEntityId());
            } catch (\Exception $exception) {
                $output->writeln('Exception when sync customer with ID: ' . $customer->getEntityId());
                $output->writeln('Exception message: ' . $exception->getMessage());
            } catch (\Throwable $throwable) {
                $output->writeln('Throwable when sync customer with ID: ' . $customer->getEntityId());
                $output->writeln('Throwable message: ' . $throwable->getMessage());
            }
        }
        $output->writeln("All Process Done");
    }

    /**
     * @param $customer
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function disableAddressValidation($customer)
    {
        foreach ($customer->getAddresses() as $address) {
            $addressModel = $this->addressRegistry->retrieve($address->getId());
            $addressModel->setShouldIgnoreValidation(true);
        }
    }

    /**
     * @return array
     */
    private function getWebsiteApply()
    {
        $storeApply = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (in_array($store->getCode(), self::STORE_CODE_APPLY)) {
                $storeApply[] = $store->getId();
            }
        }
        return $storeApply;
    }
}
