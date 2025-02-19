<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: bilalyounas
 * Date: 7/10/20
 * Time: 5:50 PM
 */
declare(strict_types=1);

namespace Eguana\NewsBoard\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class for the backbutton toolbar on create/edit NewsBoard
 *
 * Class BackButton
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get back button data
     *
     * @return array
     */
    public function getButtonData() : array
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
     *
     * @return string
     */
    private function getBackUrl() : string
    {
        return $this->getUrl('*/*/');
    }
}
