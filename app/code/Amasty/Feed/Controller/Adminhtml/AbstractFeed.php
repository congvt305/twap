<?php

namespace Amasty\Feed\Controller\Adminhtml;

/**
 * Class AbstractFeed
 *
 * @package Amasty\Feed
 */
abstract class AbstractFeed extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin action
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_Feed::feed';
}
