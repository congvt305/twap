<?php
declare(strict_types=1);
/**
 * @author Eguana Team
 * Created by PhpStorm
 * User: Sonia Park
 * Date: 07/16/2021
 */

namespace Amore\GcrmSegment\Model\ResourceModel;

use Psr\Log\LoggerInterface;

class Segment extends \Magento\CustomerSegment\Model\ResourceModel\Segment
{
    /**
     * @var \Amore\GcrmDataExport\Model\Config\Config
     */
    private $dataExportConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * Segment constructor.
     * @param LoggerInterface $logger
     * @param \Amore\GcrmDataExport\Model\Config\Config $dataExportConfig
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Quote\Model\ResourceModel\Quote $resourceQuote
     * @param \Magento\Quote\Model\QueryResolver $queryResolver
     * @param string $connectionName
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Amore\GcrmDataExport\Model\Config\Config $dataExportConfig,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Quote\Model\ResourceModel\Quote $resourceQuote,
        \Magento\Quote\Model\QueryResolver $queryResolver,
        $connectionName = null
    ) {
        parent::__construct($context, $resourceHelper, $configShare, $dateTime, $resourceQuote, $queryResolver, $connectionName);
        $this->dataExportConfig = $dataExportConfig;
        $this->logger = $logger;
    }

    /**
     * Process conditions.
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     * @throws \Exception
     */
    protected function processConditions($segment)
    {
        $websiteIds = $segment->getWebsiteIds();
        $relatedCustomers = [];
        if (!empty($websiteIds)) {
            $relatedCustomers = $this->getRelatedCustomers($segment, $websiteIds);
        }
        $this->saveSegmentCustomers($relatedCustomers, $segment);
        return $this;
    }
    /**
     * Save customers segment
     *
     * @param iterable $relatedCustomers
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     * @throws \Exception
     */
    private function saveSegmentCustomers(
        iterable $relatedCustomers,
        \Magento\CustomerSegment\Model\Segment $segment
    ) {
        $connection = $this->getConnection();
        $customerTable = $this->getTable('magento_customersegment_customer');
        $segmentId = $segment->getId();
        $now = $this->dateTime->formatDate(time());
        $data = [];
        $count = 0;
        $connection->beginTransaction();
        try {
            foreach ($relatedCustomers as $customer) {
                $data[] = [
                    'segment_id' => $segmentId,
                    'customer_id' => $customer['entity_id'],
                    'website_id' => $customer['website_id'],
                    'added_date' => $now,
                    'updated_date' => $now,
                ];
                $count++;
                if ($count % 1000 == 0) {
                    $connection->insertMultiple($customerTable, $data);
                    $data = [];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($customerTable, $data);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        $connection->commit();

        return $this;
    }

    /**
     * Retrieve customers that where matched by segment and website id
     *
     * @param \Magento\CustomerSegment\Model\Segment $segment
     * @param array $websiteIds
     * @return \Generator
     */
    private function getRelatedCustomers($segment, $websiteIds): \Generator
    {
        $customerIds = [];
        foreach ($websiteIds as $websiteId) {
            if ($this->_configShare->isGlobalScope() && empty($customerIds)) {
                $customerIds = $segment->getDataByKey('is_remote') && $segment->getDataByKey('remote_code') ?
                    $this->getRemoteCustomers($segment->getDataByKey('remote_code'), $websiteId)
                    : $segment->getConditions()->getSatisfiedIds(null);
            } elseif ($this->_configShare->isWebsiteScope()) {
                $customerIds = $segment->getDataByKey('is_remote') && $segment->getDataByKey('remote_code') ?
                    $this->getRemoteCustomers($segment->getDataByKey('remote_code'), $websiteId)
                    : $segment->getConditions()->getSatisfiedIds($websiteId);
            }
            //get customers ids that satisfy conditions
            foreach ($customerIds as $customerId) {
                yield [
                    'entity_id' => $customerId,
                    'website_id' => $websiteId,
                ];
            }
        }
    }

    /**
     * @param string $remoteCode
     * @param int|null $websiteId
     * @return array
     */
    private function getRemoteCustomers($remoteCode, $websiteId=null)
    {
        $customerIds = [];
        try {
            $host = $this->dataExportConfig->getHerokuHost();
            $dbname = $this->dataExportConfig->getHerokuDBName();
            $user = $this->dataExportConfig->getHerokuUser();
            $password = $this->dataExportConfig->getHerokuPassword();
            $db_connection = pg_connect(
                "host=$host
                         dbname=$dbname
                         user=$user
                         password=$password"
            );
            $query = "select customer_id__c from apgcrm.GECPBannerD__c where isdeleted = 'f' AND segment_id__c = '". $remoteCode . "'";
            if ($websiteId) {
                $query .= " AND website_id__c = '" . $websiteId . "'";
            }
            $result = pg_query($db_connection, $query);
            $resultArr = pg_fetch_all_columns($result);
            pg_close($db_connection);
            if ($resultArr != false) {
                $customerIds = $resultArr;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $customerIds;
    }
}
