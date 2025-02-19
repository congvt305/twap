<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: yasir
 * Date: 6/17/20
 * Time: 5:49 AM
 */
namespace Eguana\Magazine\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class for the backbutton toolbar on create/edit magazine
 * Class BackButton
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * Used for Back Button
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10,
        ];
    }

    /**
     * Get URL for back (reset) button
     * @return string
     */
    private function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}
