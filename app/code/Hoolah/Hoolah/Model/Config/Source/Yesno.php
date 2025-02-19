<?php
    namespace Hoolah\Hoolah\Model\Config\Source;
    
    class Yesno implements \Magento\Framework\Option\ArrayInterface
    {
        public function toOptionArray()
        {
            return [['value' => 1, 'label' => __('Yes (recommended)')], ['value' => 0, 'label' => __('No')]];
        }
        
        public function toArray()
        {
            return [0 => __('No'), 1 => __('Yes (recommended)')];
        }
    }
