<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Consumer;

use Dotdigital\V3\Models\Contact;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Email\Model\Apiconnector\Client as V2Client;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsUnsubscribeData;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Http\Client\Exception;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Exception\LocalizedException;

class MarketingSmsUnsubscribe
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

    /**
     * @var Configuration
     */
    private $smsConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var V3Client
     */
    private $v3Client;

    /**
     * @var V2Client
     */
    private $v2Client;

    /**
     * @param V3ClientFactory $clientFactory
     * @param Logger $logger
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResponseHandler $contactResponseHandler
     * @param Configuration $smsConfig
     * @param Data $helper
     */
    public function __construct(
        V3ClientFactory $clientFactory,
        Logger $logger,
        DotdigitalContactFactory $sdkContactFactory,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResponseHandler $contactResponseHandler,
        Configuration $smsConfig,
        Data $helper
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->sdkContactFactory = $sdkContactFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->smsConfig = $smsConfig;
        $this->helper = $helper;
    }

    /**
     * Process.
     *
     * @param MarketingSmsUnsubscribeData $unsubscribeData
     *
     * @return void
     * @throws Exception
     */
    public function process(MarketingSmsUnsubscribeData $unsubscribeData): void
    {
        $this->v2Client = $this->helper->getWebsiteApiClient((int)$unsubscribeData->getWebsiteId());
        $this->v3Client = $this->clientFactory->create(['data' => ['websiteId' => $unsubscribeData->getWebsiteId()]]);
        $contact = $this->getContact($unsubscribeData);

        if (!empty($contact)) {
            try {
                $this->v2Client->deleteAddressBookContact(
                    $this->smsConfig->getListId($unsubscribeData->getWebsiteId()),
                    $contact->getContactId()
                );
                $this->logger->info(
                    "Contact marketing SMS subscribe success:",
                    [
                        'identifier' => $contact->getIdentifiers()
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->warning(
                    "Contact marketing SMS unsubscribe warning:",
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
     * @param MarketingSmsUnsubscribeData $unsubscribeData
     *
     * @return Contact|null
     * @throws Exception
     */
    private function getContact(MarketingSmsUnsubscribeData $unsubscribeData): ?Contact
    {
        try {
            return $this->v3Client->contacts->getByIdentifier($unsubscribeData->getEmail());
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
}
