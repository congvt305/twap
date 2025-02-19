<?php

namespace CJ\Catalog\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product as ProductModel;

class AddVisualAidsAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return AddVisualAidsAttribute|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(ProductModel::ENTITY,'extra_promotion_description', [
            'type' => 'text',
            'label' => 'Extra Promotion Description',
            'input' => 'textarea',
            'source' => \Magento\Catalog\Model\Product\Attribute\Source\Layout::class,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'user_defined' => true,
            'visible' => true,
            'required' => false,
            'default' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_visible_on_front' => true
        ]);

        $eavSetup->addAttribute(ProductModel::ENTITY, 'free_sample', [
            'type' => 'text',
            'label' => 'Free Sample',
            'input' => 'textarea',
            'source' => \Magento\Catalog\Model\Product\Attribute\Source\Layout::class,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'user_defined' => true,
            'visible' => true,
            'required' => false,
            'default' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_visible_on_front' => true
        ]);

        $eavSetup->addAttribute(ProductModel::ENTITY, 'points_description', [
            'type' => 'text',
            'label' => 'Points Description',
            'input' => 'textarea',
            'source' => \Magento\Catalog\Model\Product\Attribute\Source\Layout::class,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'user_defined' => true,
            'visible' => true,
            'required' => false,
            'default' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_visible_on_front' => true
        ]);
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
