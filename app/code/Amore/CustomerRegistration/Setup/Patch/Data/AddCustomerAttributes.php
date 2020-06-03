<?php
/**
 * @author Eguana Team
 * @copyright Copyright (c) 2019 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: Abbas
 * Date: 05/20/20
 * Time: 12:11 PM
 */
namespace Amore\CustomerRegistration\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\ResourceModel\Attribute as CustomerAttributeResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for add attribute in customer
 *
 * Class AddTelephoneAttribute
 *
 */
class AddCustomerAttributes implements DataPatchInterface
{

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var CustomerAttributeResource
     */
    private $customerAttributeResource;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepositoryInterface;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AddTelephoneAttribute constructor.
     * @param EavSetup $eavSetup
     * @param EavConfig $eavConfig
     * @param CustomerAttributeResource $customerAttributeResource
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        EavSetup $eavSetup,
        EavConfig $eavConfig,
        CustomerAttributeResource $customerAttributeResource,
        AttributeRepositoryInterface $attributeRepositoryInterface,
        LoggerInterface $logger
    ) {
        $this->eavConfig                 = $eavConfig;
        $this->eavSetup                  = $eavSetup;
        $this->customerAttributeResource = $customerAttributeResource;
        $this->attributeRepositoryInterface = $attributeRepositoryInterface;
        $this->logger = $logger;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    /**
     * This function is responsible for create telephone attribute
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $textFieldAttributes = [
                                    ['code'=>'mobile_number','label'=>'Mobile number', 'type' => 'int', 'validation'=>'{"input_validation":"alphanumeric","max_text_length":20,"min_text_length":5}', 'requried' => 1],
                                    ['code'=>'dm_city','label'=>'DM City', 'type' => 'varchar', 'validation'=>'{"input_validation":"alphanum-with-spaces","max_text_length":255,"min_text_length":1}', 'requried' => 0],
                                    ['code'=>'dm_state','label'=>'DM State', 'type' => 'varchar', 'validation'=>'{"input_validation":"alphanum-with-spaces","max_text_length":255,"min_text_length":1}', 'requried' => 0],
                                    ['code'=>'dm_detailed_address','label'=>'DM Detailed Address', 'type' => 'varchar', 'validation'=>'{"input_validation":"alphanum-with-spaces","max_text_length":255,"min_text_length":1}', 'requried' => 0],
                                    ['code'=>'dm_zipcode','label'=>'DM Zipcode', 'type' => 'int', 'validation'=>'{"input_validation":"numeric","max_text_length":20,"min_text_length":1}', 'requried' => 0],
                                    ['code'=>'favorite_store','label'=>'Favorite Store', 'type' => 'varchar', 'validation'=>'{"input_validation":"alphanum-with-spaces","max_text_length":255,"min_text_length":1}', 'requried' => 0],
                                    ['code'=>'referrer_code','label'=>'Referrer Code', 'type' => 'varchar', 'validation'=>'{"input_validation":"alphanum-with-spaces","max_text_length":255,"min_text_length":1}', 'requried' => 0]
                                ];
        $yesNoAttributes = [
//                                ['code'=>'email_subscription_status','label'=>'Email Marketing'],
                                ['code'=>'sms_subscription_status','label'=>'SMS Marketing'],
                                ['code'=>'dm_subscription_status','label'=>'DM Marketing']
                            ];


        foreach ($textFieldAttributes as $textFieldAttribute)
        {
            $this->addCustomerAttributeTextFiled($textFieldAttribute['code'],
                                                 $textFieldAttribute['label'],
                                                 $textFieldAttribute['type'],
                                                 $textFieldAttribute['validation'],
                                                 $textFieldAttribute['requried'],
                                                );
        }

        foreach ($yesNoAttributes as $yesNoAttribute)
        {
            $this->addCustomerAttributeBoolean($yesNoAttribute['code'],
                                                 $yesNoAttribute['label']
                                                );
        }
    }

    /**
     * To create a text field attribute for customer
     * This function will create a text field customer attribute based on code, label, type, validation and required
     * @param $code
     * @param $label
     * @param $type
     * @param $validation
     */
    private function addCustomerAttributeTextFiled($code, $label, $type, $validation, $required)
    {
        $attribute = $this->customerAttributeResource->
        getIdByCode(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $code
        );
        if (!$attribute) {
            try {
                $this->eavSetup->addAttribute(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    $code,
                    [
                        'label' => $label,
                        'type' => $type,
                        'input' => 'text',
                        'validate_rules' => $validation,
                        'required' => $required,
                        'system' => 0,
                        'sort_order' => 100,
                        'user_defined' => 1,
                        'group' => 'General'
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $this->assignAttributeToForms($code);
        }
    }

    /**
     * To create a boolean field attribute for customer
     * This function will create a boolean customer attribute based on code and label
     * @param $code
     * @param $label
     */
    private function addCustomerAttributeBoolean($code, $label)
    {
        $attribute = $this->customerAttributeResource->
        getIdByCode(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $code
        );
        if (!$attribute) {
            try {
                $this->eavSetup->addAttribute(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    $code,
                    [
                        'label' => $label,
                        'type' => 'int',
                        'input' => 'select',
                        'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                        'required' => 0,
                        'system' => 0,
                        'sort_order' => 100,
                        'user_defined' => 1,
                        'group' => 'General'
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $this->assignAttributeToForms($code);
        }
    }

    /**
     * This function is responsible for add attribute to create account page
     *
     */
    private function assignAttributeToForms($code)
    {
        try {
            $attribute = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $code
            );
            $attribute->setData('used_in_forms', [
                'adminhtml_customer',
                'customer_account_create'
            ]);

            $this->customerAttributeResource->save($attribute);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
