<?php

namespace Dotdigitalgroup\Sms\Model\Sync;

use DateTime;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Sync\Batch\SmsSubscriberBatchProcessor;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Store\Api\Data\WebsiteInterface;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;

class SmsSubscriber implements SyncInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $emailHelper;

    /**
     * @var Configuration
     */
    private $smsConfig;

    /**
     * @var int
     */
    private $syncedCount = 0;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ExporterFactory
     */
    private $exporterFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var SmsSubscriberBatchProcessor
     */
    private $batchProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Logger $logger
     * @param Data $emailHelper
     * @param Configuration $smsConfig
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ExporterFactory $exporterFactory
     * @param ResourceConnection $resource
     * @param SmsSubscriberBatchProcessor $batchProcessor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        Data $emailHelper,
        Configuration $smsConfig,
        ContactCollectionFactory $contactCollectionFactory,
        ExporterFactory $exporterFactory,
        ResourceConnection $resource,
        SmsSubscriberBatchProcessor $batchProcessor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->emailHelper = $emailHelper;
        $this->smsConfig = $smsConfig;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->exporterFactory = $exporterFactory;
        $this->resource = $resource;
        $this->batchProcessor = $batchProcessor;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Sync SMS subscribers.
     *
     * @param DateTime|null $from
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sync(DateTime $from = null): array
    {
        $start = microtime(true);
        $breakValue = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE
        );
        $megaBatchSize = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT
        );

        foreach ($this->fetchWebsitesForSync() as $website) {
            $smsAddressBookListId = $this->smsConfig->getListId($website->getId());
            if (empty($smsAddressBookListId)) {
                continue;
            }
            $this->loopByWebsite(
                $website,
                $megaBatchSize,
                $breakValue
            );
        }

        $message = '----------- SMS Subscriber sync ----------- : '
            . gmdate('H:i:s', (int) (microtime(true) - $start))
            . ', Total synced = ' . $this->syncedCount;

        if ($this->syncedCount > 0 || $this->emailHelper->isDebugEnabled()) {
            $this->logger->info($message);
        }

        return [
            'message' => $message,
            'syncedSubscribers' => $this->syncedCount
        ];
    }

    /**
     * Perform batching loop by website.
     *
     * @param WebsiteInterface $website
     * @param int $megaBatchSize
     * @param int $breakValue
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loopByWebsite(WebsiteInterface $website, int $megaBatchSize, int $breakValue): void
    {
        $limit = $this->smsConfig->getLimit($website->getId());
        $exportKeyFieldMapping = $this->getValuesForMappedFields($website);
        $offset = 0;
        $megaBatch = [];

        do {
            $subscribers = $this->getSmsSubscribers($website, $limit, $offset);
            if (!count($subscribers->getItems())) {
                break;
            }

            $batch = $this->exporterFactory
                    ->create()
                    ->setFieldMapping($exportKeyFieldMapping)
                    ->export($subscribers, $website);

            $batchCount = count($batch);

            if ($batchCount === 0) {
                break;
            }

            $megaBatch = $megaBatch + $batch;
            $offset += count($subscribers->getItems());
            $this->syncedCount += $batchCount;

            if (count($megaBatch) >= $megaBatchSize) {
                $this->batchProcessor->process($megaBatch, $website->getId());
                $megaBatch = [];
                $offset = 0;
            }
        } while (!$breakValue || $this->syncedCount < $breakValue);

        $this->batchProcessor->process($megaBatch, $website->getId());
    }

    /**
     * Get all websites for sync.
     *
     * @return array
     * @throws LocalizedException
     */
    private function fetchWebsitesForSync(): array
    {
        return array_filter($this->emailHelper->getWebsites(), function ($website) {
            return $this->emailHelper->isEnabled($website) && $this->smsConfig->isSmsSyncEnabled($website->getId());
        });
    }

    /**
     * Get Sms subscribers to import.
     *
     * @param WebsiteInterface $website
     * @param int $limit
     * @param int $offset
     *
     * @return Collection
     */
    private function getSmsSubscribers(WebsiteInterface $website, int $limit, int $offset): Collection
    {
        $smsSubscriberCollection = $this->contactCollectionFactory->create()
            ->addFieldToFilter('main_table.website_id', ['eq' => $website->getId()])
            ->addFieldToFilter('sms_subscriber_status', Subscriber::STATUS_SUBSCRIBED)
            ->addFieldToFilter('sms_subscriber_imported', Contact::EMAIL_CONTACT_NOT_IMPORTED)
            ->addFieldToFilter('mobile_number', ['notnull' => true ])
            ->addFieldToFilter('mobile_number', ['neq' => '']);

        $smsSubscriberCollection->getSelect()
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'main_table.customer_id = customer.entity_id',
                [
                    'customer.firstname',
                    'customer.lastname'
                ]
            )
            ->joinLeft(
                ['website' => $this->getTable('store_website')],
                'main_table.website_id = website.website_id',
                [
                    'website.name as website_name'
                ]
            )
            ->joinLeft(
                ['store' => $this->getTable('store')],
                'main_table.store_id = store.store_id',
                [
                    'store.name as store_name',
                ]
            )
            ->joinLeft(
                ['store_group' => $this->getTable('store_group')],
                'main_table.store_id = store_group.group_id',
                [
                    'store_group.name as store_name_additional',
                ]
            )
            ->limit($limit, $offset);

        return $smsSubscriberCollection;
    }

    /**
     * Get table name with prefix support
     *
     * @param string $name
     * @return string
     */
    public function getTable(string $name):string
    {
        return $this->resource->getTableName($name);
    }

    /**
     * Get the columns for the mapped fields
     *
     * @param WebsiteInterface $website
     * @return array
     */
    private function getValuesForMappedFields(WebsiteInterface $website): array
    {
        return array_filter($this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            ScopeInterface::SCOPE_WEBSITES,
            $website->getId()
        ), function ($key) {
            return in_array($key, Exporter::DATA_KEYS);
        }, ARRAY_FILTER_USE_KEY);
    }
}
