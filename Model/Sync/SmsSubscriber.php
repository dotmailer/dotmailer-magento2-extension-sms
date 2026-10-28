<?php

namespace Dotdigitalgroup\Sms\Model\Sync;

use DateTime;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Sync\Batch\SmsSubscriberBatchProcessor;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
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
     * @var ExporterFactory
     */
    private $exporterFactory;

    /**
     * @var SmsSubscriberBatchProcessor
     */
    private $batchProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RetrieverFactory
     */
    private $retrieverFactory;

    /**
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * @param Logger $logger
     * @param Data $emailHelper
     * @param Configuration $smsConfig
     * @param ExporterFactory $exporterFactory
     * @param SmsSubscriberBatchProcessor $batchProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param RetrieverFactory $retrieverFactory
     * @param RegistrationStoreResolver $registrationStoreResolver
     */
    public function __construct(
        Logger $logger,
        Data $emailHelper,
        Configuration $smsConfig,
        ExporterFactory $exporterFactory,
        SmsSubscriberBatchProcessor $batchProcessor,
        ScopeConfigInterface $scopeConfig,
        RetrieverFactory $retrieverFactory,
        RegistrationStoreResolver $registrationStoreResolver
    ) {
        $this->logger = $logger;
        $this->emailHelper = $emailHelper;
        $this->smsConfig = $smsConfig;
        $this->exporterFactory = $exporterFactory;
        $this->batchProcessor = $batchProcessor;
        $this->scopeConfig = $scopeConfig;
        $this->retrieverFactory = $retrieverFactory;
        $this->registrationStoreResolver = $registrationStoreResolver;
    }

    /**
     * Sync SMS subscribers.
     *
     * @param DateTime|null $from
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sync(?DateTime $from = null): array
    {
        $start = microtime(true);
        $breakValue = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);
        $megaBatchSize = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT
        );

        foreach ($this->fetchWebsitesForSync() as $website) {
            $megaBatch = [];
            foreach ($this->getListCohorts($website) as $storeIds) {
                $this->loopByListCohort($website, $storeIds, $megaBatchSize, $breakValue, $megaBatch);
                if ($breakValue && $this->syncedCount >= $breakValue) {
                    break;
                }
            }
            if ($megaBatch) {
                $this->batchProcessor->process($megaBatch, $website->getId());
            }
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
     * Group stores sharing the same inherited or overridden SMS list.
     *
     * @param WebsiteInterface $website
     * @return array
     */
    private function getListCohorts(WebsiteInterface $website): array
    {
        $cohorts = [];
        $websiteId = (int) $website->getId();
        foreach ($this->registrationStoreResolver->getWebsiteStoreIds($websiteId) as $storeId) {
            $listId = $this->smsConfig->getListId($websiteId, $storeId);
            if ($listId) {
                $cohorts[$listId][] = $storeId;
            }
        }
        return $cohorts;
    }

    /**
     * Perform batching loop for one list cohort.
     *
     * All cohorts of the website share a mega batch; each contact carries its own list.
     *
     * @param WebsiteInterface $website
     * @param int[] $storeIds
     * @param int $megaBatchSize
     * @param int $breakValue
     * @param array $megaBatch
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loopByListCohort(
        WebsiteInterface $website,
        array $storeIds,
        int $megaBatchSize,
        int $breakValue,
        array &$megaBatch
    ): void {
        $limit = $this->smsConfig->getLimit($website->getId());
        $exportKeyFieldMapping = $this->getValuesForMappedFields($website);
        $offset = 0;

        do {
            $subscribers = $this->retrieverFactory
                ->create()
                ->setWebsite($website->getId())
                ->getSmsSubscribers($limit, $offset, $storeIds);

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
            return $this->emailHelper->isEnabled($website->getId())
                && $this->smsConfig->isSmsSyncEnabled($website->getId());
        });
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
