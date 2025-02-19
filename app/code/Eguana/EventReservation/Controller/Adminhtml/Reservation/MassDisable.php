<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: raheel
 * Date: 16/10/20
 * Time: 12:20 PM
 */
namespace Eguana\EventReservation\Controller\Adminhtml\Reservation;

use Eguana\EventReservation\Model\ResourceModel\Event\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * To disable multiple events
 *
 * Class MassDisable
 */
class MassDisable extends Action implements HttpPostActionInterface
{
    /**#@+
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Eguana_EventReservation::event_reservation';
    /**#@-*/

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter               = $filter;
        $this->collectionFactory    = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action to disable events
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            foreach ($collection as $item) {
                $item->setIsActive(false);
                $item->save();
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 event(s) have been disabled.', $collection->getSize())
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __($e->getMessage()));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
