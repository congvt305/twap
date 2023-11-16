<?php

namespace Amasty\Shopby\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class RenderCategoriesTree implements ArrayInterface
{
    public const NO = 0;
    public const YES = 1;

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NO,
                'label' => __('No')
            ],
            [
                'value' => self::YES,
                'label' => __('Yes')
            ],
        ];
    }
}
