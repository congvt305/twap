<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 7/18/20
 * Time: 6:20 AM
 */

namespace Eguana\GWLogistics\Model\Service;


use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Rma\Api\RmaRepositoryInterface;

class ReverseOrderStatusNotificationHandler
{
    /**
     * @var \Eguana\GWLogistics\Helper\Data
     */
    private $dataHelper;
    /**
     * @var \Magento\Rma\Api\TrackRepositoryInterface
     */
    private $trackRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Rma\Api\Data\CommentInterfaceFactory
     */
    private $commentInterfaceFactory;
    /**
     * @var \Magento\Rma\Api\CommentRepositoryInterface
     */
    private $commentRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Eguana\GWLogistics\Api\Data\ReverseStatusNotificationInterfaceFactory
     */
    private $reverseStatusNotificationFactory;
    /**
     * @var \Eguana\GWLogistics\Api\ReverseStatusNotificationRepositoryInterface
     */
    private $statusNotificationRepository;
    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    public function __construct(
        \Eguana\GWLogistics\Helper\Data $dataHelper,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Rma\Api\TrackRepositoryInterface $trackRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Rma\Api\Data\CommentInterfaceFactory $commentInterfaceFactory,
        \Magento\Rma\Api\CommentRepositoryInterface $commentRepository,
        \Eguana\GWLogistics\Api\Data\ReverseStatusNotificationInterfaceFactory $reverseStatusNotificationFactory,
        \Eguana\GWLogistics\Api\ReverseStatusNotificationRepositoryInterface $statusNotificationRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->trackRepository = $trackRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->commentInterfaceFactory = $commentInterfaceFactory;
        $this->commentRepository = $commentRepository;
        $this->logger = $logger;
        $this->reverseStatusNotificationFactory = $reverseStatusNotificationFactory;
        $this->statusNotificationRepository = $statusNotificationRepository;
        $this->rmaRepository = $rmaRepository;
    }

    /*
     * $notificationData:  {
    "MerchantID":"2000132",
    "MerchantTradeNo":"1592515364346",
    "RtnCode":"UNIMART",
    "RtnMsg":"UNIMART",
    "AllPayLogisticsID":"UNIMART",
    "GoodsAmount":"UNIMART",
    "UpdateStatusDate":"UNIMART",
    "BookingNote":""
    "CheckMacValue":""
    }
    */
    public function process(array $notificationData)
    {
        if (isset($notificationData['RtnMsg'], $notificationData['RtnCode'], $notificationData['UpdateStatusDate'], $notificationData['RtnMerchantTradeNo'])) {
            try {
                $this->logger->info('gwlogistics | ' . $notificationData['RtnMerchantTradeNo']);
                $rmaId = $this->findRmaId($notificationData['RtnMerchantTradeNo']);

                if ($rmaId) {
                    $rma = $this->rmaRepository->get($rmaId);
                    $storeId = $rma->getStoreId();

                    try {
                        $this->dataHelper->validateCheckMackValue($notificationData, $storeId);
                    } catch (\Exception $e) {
                        $this->logger->info('gwlogistics | CheckMacValue no vailid for reverse order notification for rma id #' . $rma->getIncrementId());
                    }

                    /** @var \Magento\Rma\Api\Data\CommentInterface $rmaComment */
                    $rmaComment = $this->commentInterfaceFactory->create();
                    $rmaComment->setRmaEntityId($rmaId);
                    $rmaComment->setIsVisibleOnFront(1);
                    $rmaComment->setIsAdmin(1);
                    $rmaComment->setIsCustomerNotified(0);
                    $rmaComment->setComment($this->makeComments($notificationData['RtnMsg'], $notificationData['RtnCode'], $notificationData['UpdateStatusDate']));
//                    $rmaComment->setStatus('...'); //todo: handle status depends on Return Code for exameple received..
                    $this->commentRepository->save($rmaComment);

                    /** @var \Eguana\GWLogistics\Api\Data\ReverseStatusNotificationInterface $statusNotification */
                    $statusNotification = $this->reverseStatusNotificationFactory->create();
                    $statusNotification->setRmaId($rmaId);
                    $statusNotification->setMerchantId($notificationData['MerchantID']);
                    $statusNotification->setRtnMerchantTradeNo($notificationData['RtnMerchantTradeNo']);
                    $statusNotification->setRtnCode($notificationData['RtnCode']);
                    $statusNotification->setRtnMsg($notificationData['RtnMsg']);
                    $statusNotification->setAllPayLogisticsId($notificationData['AllPayLogisticsID']);
                    $statusNotification->setGoodsAmount($notificationData['GoodsAmount']);
                    $statusNotification->setUpdateStatusDate($notificationData['UpdateStatusDate']);
                    $this->statusNotificationRepository->save($statusNotification);
                    return true;
                }
            } catch (\Exception $e) {
                $this->logger->info('gwlogistics | Could no save for reverse order notification for RtnMerchantTradeNo #' . $notificationData['RtnMerchantTradeNo'], [$e->getMessage()]);
                return false;
            }
        }
    }

    private function findRmaId(string $rtnMerchantTradeNo)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('rtn_merchant_trade_no', $rtnMerchantTradeNo)
            ->create();
        $tracks = $this->trackRepository
            ->getList($searchCriteria)
            ->getItems();
        $track = reset($tracks);

        $this->logger->info('gwlogistics | rma '. ($track ? $track->getRmaEntityId() : 'Rma entity id not found'));
        return $track ? $track->getRmaEntityId() : false;

    }
    private function makeComments($message, $code, $date)
    {
        return $message . '|' . $code . '|' . $date . '|' . __('Notified by Green World Logistics.');
    }

}
