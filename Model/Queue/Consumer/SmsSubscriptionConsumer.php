<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigital\V3\Models\Contact;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Http\Client\Exception;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionData;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;

class SmsSubscriptionConsumer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var V3ClientFactory
     */
    private $v3ClientFactory;

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
     * @param Data $helper
     * @param Logger $logger
     * @param V3ClientFactory $v3ClientFactory
     * @param Configuration $smsConfig
     * @param ExporterFactory $exporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param RetrieverFactory $retrieverFactory
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        V3ClientFactory $v3ClientFactory,
        Configuration $smsConfig,
        ExporterFactory $exporterFactory,
        ScopeConfigInterface $scopeConfig,
        RetrieverFactory $retrieverFactory
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->v3ClientFactory = $v3ClientFactory;
        $this->smsConfig = $smsConfig;
        $this->exporterFactory = $exporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->retrieverFactory = $retrieverFactory;
    }

    /**
     * Execute.
     *
     * @param SmsSubscriptionData $data
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function process(SmsSubscriptionData $data): void
    {
        if (!$data->getType()) {
            throw new LocalizedException(__('Unknown subscription type'));
        }

        $v3Client = $this->v3ClientFactory->create(['data' => ['websiteId' => $data->getWebsiteId()]]);

        switch ($data->getType()) {
            case 'subscribe':
                $this->subscribe($data, $v3Client);
                break;
            case 'unsubscribe':
                $this->unsubscribe($data, $v3Client);
                break;
        }
    }

    /**
     * Subscribe.
     *
     * @param SmsSubscriptionData $smsSubscribeData
     * @param V3Client $v3Client
     *
     * @return void
     * @throws Exception
     */
    private function subscribe(SmsSubscriptionData $smsSubscribeData, V3Client $v3Client)
    {
        try {
            $smsSubscriber = $this->retrieverFactory
                ->create()
                ->setWebsite($smsSubscribeData->getWebsiteId())
                ->getSmsSubscriber($smsSubscribeData->getContactId());

            $contact = $this->exporterFactory
                ->create()
                ->setFieldMapping($this->getValuesForMappedFields($smsSubscribeData->getWebsiteId()))
                ->prepareContact($smsSubscriber);

            $contact->setLists([$this->smsConfig->getListId($smsSubscribeData->getWebsiteId())]);

            $v3Client->contacts->patchByIdentifier(
                $smsSubscriber->getEmail(),
                $contact
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
     * Unsubscribe.
     *
     * @param SmsSubscriptionData $unsubscribeData
     * @param V3Client $v3Client
     *
     * @return void
     * @throws Exception
     */
    public function unsubscribe(SmsSubscriptionData $unsubscribeData, V3Client $v3Client): void
    {
        $v2Client = $this->helper->getWebsiteApiClient($unsubscribeData->getWebsiteId());
        $contact = $this->getContact($unsubscribeData, $v3Client);

        if (!empty($contact)) {
            try {
                $v2Client->deleteAddressBookContact(
                    $this->smsConfig->getListId($unsubscribeData->getWebsiteId()),
                    $contact->getContactId()
                );
                $this->logger->info(
                    "Contact marketing SMS unsubscribe success:",
                    [
                        'identifiers' => $contact->getIdentifiers()
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    "Contact marketing SMS unsubscribe error:",
                    [
                        'identifier' => $unsubscribeData->getEmail(),
                        'exception' => $e,
                    ]
                );
            }
        }
    }

    /**
     * Get contact.
     *
     * @param SmsSubscriptionData $unsubscribeData
     * @param V3Client $v3Client
     *
     * @return Contact|null
     * @throws Exception
     */
    private function getContact(SmsSubscriptionData $unsubscribeData, V3Client $v3Client): ?Contact
    {
        try {
            return $v3Client->contacts->getByIdentifier($unsubscribeData->getEmail());
        } catch (\Exception $e) {
            $this->logger->warning(
                "Contact marketing SMS unsubscribe warning:",
                [
                    'identifier' => $unsubscribeData->getEmail(),
                    'exception' => $e,
                ]
            );
        }
        return null;
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
