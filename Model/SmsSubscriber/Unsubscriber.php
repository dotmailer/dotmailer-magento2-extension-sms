<?php

namespace Dotdigitalgroup\Sms\Model\SmsSubscriber;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact\ContactUpdaterInterface;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact as ContactResource;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Exception;

class Unsubscriber implements ContactUpdaterInterface
{
    private const UNSUBSCRIBED_STATUS_STRING = 'unsubscribed';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CronFromTimeSetter
     */
    private $cronFromTimeSetter;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @param Logger $logger
     * @param CronFromTimeSetter $cronFromTimeSetter
     * @param ContactResource $contactResource
     * @param ContactCollectionFactory $contactCollectionFactory
     */
    public function __construct(
        Logger $logger,
        CronFromTimeSetter $cronFromTimeSetter,
        ContactResource $contactResource,
        ContactCollectionFactory $contactCollectionFactory
    ) {
        $this->logger = $logger;
        $this->cronFromTimeSetter = $cronFromTimeSetter;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function processBatch(array $batch, array $websiteIds)
    {
        $unsubscribes = 0;

        try {
            $unsubscribedContactsOnSmsChannel = $this->filterModifiedContacts($batch);

            if (count($unsubscribedContactsOnSmsChannel) === 0) {
                return;
            }

            $unsubscribes += $this->unsubscribeWithChangeStatusCheck(
                $unsubscribedContactsOnSmsChannel,
                $websiteIds
            );

        } catch (Exception $e) {
            $this->logger->debug('Error processing batch', [(string) $e]);
        }

        if (!$unsubscribes) {
            return;
        }

        $this->logger->info(
            sprintf(
                '%s SMS contacts unsubscribed in website ids %s',
                $unsubscribes,
                implode(',', $websiteIds)
            )
        );
    }

    /**
     * Trim the set to include only contacts who are unsubscribed on the SMS channel.
     *
     * @param Object[] $contacts
     *
     * @return array
     * @throws Exception
     */
    private function filterModifiedContacts($contacts)
    {
        return array_filter($contacts, function ($contact) {
            $smsChannelProperties = $contact->getChannelProperties()->getSms();
            return $smsChannelProperties &&
                $contact->getChannelProperties()->getSms()->getStatus() === self::UNSUBSCRIBED_STATUS_STRING;
        });
    }

    /**
     * Unsubscribe any contacts who did not subscribe more recently.
     *
     * @param array $platformContacts
     * @param array $websiteIds
     *
     * @return int
     * @throws Exception
     */
    private function unsubscribeWithChangeStatusCheck(array $platformContacts, array $websiteIds)
    {
        $platformContactMobileNumbers = array_map(function ($contact) {
            return $contact->getIdentifiers()->getMobileNumber();
        }, $platformContacts);

        $localContacts = $this->contactCollectionFactory->create()
            ->getSmsSubscribedContactsWithChangeStatusAtDate(
                $platformContactMobileNumbers,
                $websiteIds
            );

        $filteredContacts = $this->filterOutRecentSmsSubscribers(
            $localContacts,
            $platformContactMobileNumbers
        );

        // no contacts to unsubscribe?
        if (empty($filteredContacts)) {
            return 0;
        }

        return $this->contactResource->unsubscribeByWebsite(
            array_column($filteredContacts, 'mobile_number'),
            $websiteIds
        );
    }

    /**
     * Filter out any more recent subscribers in Magento.
     *
     * @param array $localContacts
     * @param array $platformContactMobileNumbers
     *
     * @return array
     * @throws Exception
     */
    private function filterOutRecentSmsSubscribers(array $localContacts, array $platformContactMobileNumbers)
    {
        return array_filter(array_map(function ($contact) use ($platformContactMobileNumbers) {
            // get corresponding platform contact
            $contactKey = array_search($contact['mobile_number'], $platformContactMobileNumbers);

            // if there is no last subscribed value, continue with unsubscribe
            if ($contactKey === false || $contact['sms_change_status_at'] === null) {
                return $contact;
            }

            // convert both timestamps to DateTime
            $lastSubscribedMagento = new \DateTime(
                $contact['sms_change_status_at'],
                new \DateTimeZone('UTC')
            );
            $utcFromTime = new \DateTime(
                $this->cronFromTimeSetter->getFromTime(),
                new \DateTimeZone('UTC')
            );

            // user recently resubscribed in Magento, do not unsubscribe them
            if ($lastSubscribedMagento > $utcFromTime) {
                return null;
            }
            return $contact;
        }, $localContacts));
    }
}
