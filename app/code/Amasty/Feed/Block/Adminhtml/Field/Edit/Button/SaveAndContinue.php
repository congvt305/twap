<?php

namespace Amasty\Feed\Block\Adminhtml\Field\Edit\Button;

class SaveAndContinue extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'on_click' => '',
            'sort_order' => 90,
        ];
    }
}
