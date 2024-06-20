<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\V3\Models\ContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Sms\Api\Queue\Message\SmsMessageInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Http\Client\Exception;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeData;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;

class MarketingSmsSubscribe
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var V3ClientFactory
     */
    private $v3ClientFactory;

    /**
     * @var V3Client
     */
    private $v3Client;

    /**
     * @var Configuration
     */
    private $smsConfig;

    /**
     * @var ExporterFactory
     */
    private $exporterFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RetrieverFactory
     */
    private $retrieverFactory;

    /**
     * MarketingSmsSubscribe constructor.
     *
     * @param Logger $logger
     * @param ContactFactory $contactFactory
     * @param V3ClientFactory $v3ClientFactory
     * @param Configuration $smsConfig
     * @param ExporterFactory $exporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param RetrieverFactory $retrieverFactory
     */
    public function __construct(
        Logger $logger,
        ContactFactory $contactFactory,
        V3ClientFactory $v3ClientFactory,
        Configuration $smsConfig,
        ExporterFactory $exporterFactory,
        ScopeConfigInterface $scopeConfig,
        RetrieverFactory $retrieverFactory
    ) {
        $this->contactFactory = $contactFactory;
        $this->v3ClientFactory = $v3ClientFactory;
        $this->logger = $logger;
        $this->smsConfig = $smsConfig;
        $this->exporterFactory = $exporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->retrieverFactory = $retrieverFactory;
    }

    /**
     * Execute.
     *
     * @param MarketingSmsSubscribeData $smsSubscribeData
     * @return void
     * @throws Exception
     */
    public function process(MarketingSmsSubscribeData $smsSubscribeData): void
    {
        $this->v3Client = $this->v3ClientFactory->create(['websiteId' => $smsSubscribeData->getWebsiteId()]);

        try {
            $smsSubscriber = $this->retrieverFactory
                ->create()
                ->setWebsite($smsSubscribeData->getWebsiteId())
                ->getSmsSubscriber($smsSubscribeData->getContactId());

            $contact = $this->exporterFactory
                ->create()
                ->setFieldMapping($this->getValuesForMappedFields($smsSubscribeData->getWebsiteId()))
                ->prepareContact($smsSubscriber, 'mobile-number');

            $contact->setLists([$this->smsConfig->getListId($smsSubscribeData->getWebsiteId())]);

            $this->v3Client->contacts->patchByIdentifier(
                $smsSubscriber->getMobileNumber(),
                $contact,
                'mobile-number'
            );

            /** @var \Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact $smsSubscriber  */
            $smsSubscriber->setSmsContactsImportedByIds([$smsSubscribeData->getContactId()]);

            $this->logger->info(
                "Contact marketing SMS subscribe success:",
                [
                    'identifiers' => $contact->getIdentifiers(),
                    'contact_id' => $smsSubscribeData->getContactId(),
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Contact marketing SMS subscribe error:",
                [
                    'identifiers' => (!empty($contact)) ? $contact->getIdentifiers() : null,
                    'contact_id' => $smsSubscribeData->getContactId(),
                    'exception' => $e,
                ]
            );
        }
    }

    /**
     * Get the columns for the mapped fields
     *
     * @param int $websiteId
     * @return array
     */
    private function getValuesForMappedFields(int $websiteId): array
    {
        return array_filter($this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        ), function ($key) {
            return in_array($key, Exporter::DATA_KEYS);
        }, ARRAY_FILTER_USE_KEY);
    }
}
