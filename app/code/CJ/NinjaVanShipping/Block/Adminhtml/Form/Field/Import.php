<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CJ\NinjaVanShipping\Block\Adminhtml\Form\Field;

/**
 * Custom import CSV file field for shipping table rates
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Import extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';

        $html .= '<input id="time_condition" type="hidden" name="' . $this->getName() . '" value="' . time() . '" />';

        $html .= <<<EndHTML
        <script>
        require(['prototype'], function(){
        Event.observe($('carriers_ninjavan_condition_name'), 'change', checkConditionName.bind(this));
        function checkConditionName(event)
        {
            var conditionNameElement = Event.element(event);
            if (conditionNameElement && conditionNameElement.id) {
                $('time_condition').value = '_' + conditionNameElement.value + '/' + Math.random();
            }
        }
        });
        </script>
EndHTML;

        $html .= parent::getElementHtml();

        return $html;
    }
}
