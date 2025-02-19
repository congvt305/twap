<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: yasir
 * Date: 6/23/20
 * Time: 7:52 AM
 */
namespace Eguana\Magazine\ViewModel\Index;

use Eguana\Magazine\Model\ResourceModel\Magazine\Collection as CollectionAlias;
use Eguana\Magazine\Model\ResourceModel\Magazine\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for get magazine data for the listing page
 * Class MagazineList
 */
class MagazineList implements ArgumentInterface
{

    const BANNER_TYPE = 1;
    const IMAGE_TYPE = 2;
    const VIDEO_TYPE = 3;
    const DETAIL_URL = 'magazine/detail/index/id/';
    const MONTHLY_DETAILS_URL = 'magazine/index/index/month/';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var RequestInterface
     */
    private $requestInterface;

    /**
     * @var LoggerInterface;
     */
    private $logger;

    /**
     * MagazineList constructor.
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param UrlInterface $urlInterface
     * @param DateTime $dateTime
     * @param RequestInterface $requestInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManagerInterface,
        UrlInterface $urlInterface,
        DateTime $dateTime,
        RequestInterface $requestInterface,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->urlInterface = $urlInterface;
        $this->dateTime = $dateTime;
        $this->requestInterface = $requestInterface;
        $this->logger = $logger;
    }

    /**
     * get magazine detail url
     * @param $id
     * @return string
     */
    public function getItemDetailsUrl($id)
    {
        return self::DETAIL_URL . $id;
    }

    /**
     * get thumbnail src for the image magazine
     * @param $thumnail
     * @return string
     */
    public function getThumbnailSrc($thumnail)
    {
        try {
            $file = ltrim(str_replace('\\', '/', $thumnail), '/');
            $thumbnailSrc = $this->storeManagerInterface->getStore()->getBaseUrl(
                UrlInterface::URL_TYPE_MEDIA
            ) . $file;
        } catch (\Exception $exception) {
            $this->logger->error($e->getMessage());
        }
        return $thumbnailSrc;
    }

    /**
     * This function will return banner type magazines
     * @return array
     */
    public function getBannerMagazines()
    {
        try {
            $currentMonthMagazines = $this->getCurrentMonthMagazines();
            $bannerMagazines = $currentMonthMagazines->addFieldToFilter(
                'type',
                ['eq' => self::BANNER_TYPE]
            );
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }

        return $bannerMagazines;
    }

    /**
     * This function will return image type magazines
     * @return array
     */
    public function geImageMagazines()
    {
        try {
            $currentMonthMagazines = $this->getCurrentMonthMagazines();
            $bannerMagazines = $currentMonthMagazines->addFieldToFilter(
                'type',
                ['eq' => self::IMAGE_TYPE]
            );
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }

        return $bannerMagazines;
    }

    /**
     * This function will return video type magazines
     * @return array
     */
    public function geVideoMagazines()
    {
        try {
            $currentMonthMagazines = $this->getCurrentMonthMagazines();
            $bannerMagazines = $currentMonthMagazines->addFieldToFilter(
                'type',
                ['eq' => self::VIDEO_TYPE]
            );
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }

        return $bannerMagazines;
    }

    /**
     * This function will return Current Month Magazines
     * @return int
     */
    public function getCurrentMonthMagazinesTotalNumber()
    {
        try {
            $currentMonthMagazines = $this->getCurrentMonthMagazines();
            return $currentMonthMagazines->getSize();
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }
        return 0;
    }

    /**
     * This function willl return Current Month Name
     * @return bool|string
     */
    public function getCurrentMonthName()
    {
        $currentMonth = $this->requestInterface->getParam('month');
        $currentYear = $this->requestInterface->getParam('year');
        if (!isset($currentMonth) && !isset($currentYear)) {
            $currentMonth = $this->dateTime->gmtDate('m');
            $currentYear = $this->dateTime->gmtDate('Y');
        }
        $query_date = sprintf('%s-%s', $currentYear, $currentMonth);
        return $this->dateTime->gmtDate('F Y', $this->dateTime->gmtTimestamp($query_date));
    }

    /**
     * This function will return Magazines of current Month
     * @return CollectionAlias
     */
    private function getCurrentMonthMagazines()
    {
        $magazineCollection = $this->getCurrentStoreActiveAndSortedMagazines();
        $dateLimits = $this->getCurrentMonthFirstAndLastDate();
        $magazineCollection->addFieldToFilter(
            'show_date',
            ['gteq' => $dateLimits['firstDate']]
        )->addFieldToFilter(
            'show_date',
            ['lteq' => $dateLimits['lastDate']]
        );

        return $magazineCollection;
    }

    /**
     * This function will return Other Magazines for slider
     * @return array
     */
    public function getOtherMonthsMagaginesFrontPages()
    {
        $result = [];
        $nextMonthsMagazineFrontPages = $this->getNextMonthMagazines();
        $previousMonthsMagazineFrontPages = $this->getPreviousMonthMagazines();
        foreach ($nextMonthsMagazineFrontPages as $nextMonthsMagazineFrontPage) {
            $magazine['thumbnail_image'] =  $nextMonthsMagazineFrontPage->getData('thumbnail_image');
            $magazine['thumbnail_alt'] =  $nextMonthsMagazineFrontPage->getData('thumbnail_alt');
            $magazine['short_description'] =  $nextMonthsMagazineFrontPage->getData('short_description');
            $magazine['title'] =  $nextMonthsMagazineFrontPage->getData('title');
            $magazine['monthly_url'] =  $this->getMonthlyMagazineUrl(
                $nextMonthsMagazineFrontPage->getData('show_date')
            );
            $result[] = $magazine;
        }

        foreach ($previousMonthsMagazineFrontPages as $previousMonthsMagazineFrontPage) {
            $magazine['thumbnail_image'] =  $previousMonthsMagazineFrontPage->getData('thumbnail_image');
            $magazine['thumbnail_alt'] =  $previousMonthsMagazineFrontPage->getData('thumbnail_alt');
            $magazine['short_description'] =  $previousMonthsMagazineFrontPage->getData('short_description');
            $magazine['title'] =  $previousMonthsMagazineFrontPage->getData('title');
            $magazine['monthly_url'] =  $this->getMonthlyMagazineUrl(
                $previousMonthsMagazineFrontPage->getData('show_date')
            );
            $result[] = $magazine;
        }

        return $result;
    }

    /**
     * This function will return Next Month Magazines
     * @return CollectionAlias
     */

    private function getNextMonthMagazines()
    {
        $nextMonthsBannerMagazines = $this->getOtherMonthBannerMagazines();
        $dateLimits = $this->getCurrentMonthFirstAndLastDate();
        $nextMonthsBannerMagazines->addFieldToFilter(
            'show_date',
            ['gt' => $dateLimits['lastDate']]
        );
        return $nextMonthsBannerMagazines;
    }

    /**
     * This function will return previous month Magazines
     * @return CollectionAlias
     */
    private function getPreviousMonthMagazines()
    {
        $previousMonthsBannerMagazines = $this->getOtherMonthBannerMagazines();
        $dateLimits = $this->getCurrentMonthFirstAndLastDate();
        $previousMonthsBannerMagazines->addFieldToFilter(
            'show_date',
            ['lt' => $dateLimits['firstDate']]
        );
        return $previousMonthsBannerMagazines;
    }

    /**
     * This function will return Banner of other Magazines
     * @return CollectionAlias
     */
    private function getOtherMonthBannerMagazines()
    {
        $magazineCollection = $this->getCurrentStoreActiveAndSortedMagazines();
        $bannerMagazines = $magazineCollection->addFieldToFilter(
            'type',
            ['eq' => self::BANNER_TYPE]
        );

        $bannerMagazines = $magazineCollection->addFieldToFilter(
            'sort_order',
            ['eq' => 1]
        );

        $bannerMagazines->setOrder(
            "show_date",
            'DESC'
        );

        return $bannerMagazines;
    }

    /**
     * This function will check active banner and return banner magazine for current store
     * @return CollectionAlias
     */
    private function getCurrentStoreActiveAndSortedMagazines()
    {
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $magazineCollection = $this->collectionFactory->create();

            $magazineCollection->addStoreFilter($storeId);
            $bannerMagazines = $magazineCollection->addFieldToFilter(
                'is_active',
                ['eq' => 1]
            );
            $magazineCollection->setOrder(
                "sort_order",
                'ASC'
            );
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }
        return $magazineCollection;
    }

    /**
     * This function will return first and last date of current month
     * @return array
     */
    private function getCurrentMonthFirstAndLastDate()
    {
        $result = [];
        $currentMonth = $this->requestInterface->getParam('month');
        $currentYear = $this->requestInterface->getParam('year');
        if (!isset($currentMonth) && !isset($currentYear)) {
            $currentMonth = $this->dateTime->gmtDate('m');
            $currentYear = $this->dateTime->gmtDate('Y');
        }
        $result['firstDate'] = $this->getFirstDateOfMonth($currentMonth, $currentYear);
        $result['lastDate'] = $this->getLastDateOfMonth($currentMonth, $currentYear);
        return $result;
    }

    /**
     * This funtion will return first date of month
     * @param $month
     * @param $year
     * @return bool|string
     */
    private function getFirstDateOfMonth($month, $year)
    {
        $query_date = sprintf('%s-%s-04', $year, $month);
        return $this->dateTime->gmtDate('Y-m-01 H:i:s', $this->dateTime->gmtTimestamp($query_date));
    }

    /**
     * This function will return last date of month
     * @param $month
     * @param $year
     * @return bool|string
     */
    private function getLastDateOfMonth($month, $year)
    {
        $query_date = sprintf('%s-%s-04 23:59:59', $year, $month);
        return $this->dateTime->gmtDate('Y-m-t H:i:s', $this->dateTime->gmtTimestamp($query_date));
    }

    /**
     * get monthly deatils url
     * @param $date
     * @return string
     */
    private function getMonthlyMagazineUrl($date)
    {
        $month = $this->dateTime->gmtDate("m", $date);
        $year = $this->dateTime->gmtDate("Y", $date);
        $date = self::MONTHLY_DETAILS_URL . $month . '/year/' . $year;
        return $date;
    }
}
