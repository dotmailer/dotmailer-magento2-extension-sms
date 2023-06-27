<?php

namespace Dotdigitalgroup\Sms\Model\SmsSubscriber;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact\ContactUpdaterInterface;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact as ContactResource;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Exception;

class Resubscriber implements ContactUpdaterInterface
{
    private const SUBSCRIBED_STATUS_STRING = 'subscribed';

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
        $resubscribes = 0;

        try {
            $subscribedContactsOnSmsChannel = $this->filterModifiedContacts($batch);

            if (count($subscribedContactsOnSmsChannel) === 0) {
                return;
            }

            $resubscribes += $this->subscribeWithChangeStatusCheck(
                $subscribedContactsOnSmsChannel,
                $websiteIds
            );
        } catch (\Exception $e) {
            $this->logger->debug('Error processing batch', [(string) $e]);
        }

        if (!$resubscribes) {
            return;
        }

        $this->logger->info(
            sprintf(
                '%s SMS contacts resubscribed in website ids %s',
                $resubscribes,
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
                $contact->getChannelProperties()->getSms()->getStatus() === self::SUBSCRIBED_STATUS_STRING;
        });
    }

    /**
     * Subscribe any contacts who did not unsubscribe more recently.
     *
     * @param array $platformContacts
     * @param array $websiteIds
     *
     * @return int
     * @throws Exception
     */
    private function subscribeWithChangeStatusCheck(array $platformContacts, array $websiteIds)
    {
        $platformContactMobileNumbers = array_map(function ($contact) {
            return $contact->getIdentifiers()->getMobileNumber();
        }, $platformContacts);

        $localContacts = $this->contactCollectionFactory->create()
            ->getSmsUnsubscribedContactsWithChangeStatusAtDate(
                $platformContactMobileNumbers,
                $websiteIds
            );
        $filteredContacts = $this->filterOutRecentSmsUnsubscribers(
            $localContacts,
            $platformContactMobileNumbers
        );

        // no contacts to unsubscribe?
        if (empty($filteredContacts)) {
            return 0;
        }

        return $this->contactResource->subscribeByWebsite(
            array_column($filteredContacts, 'mobile_number'),
            $websiteIds
        );
    }

    /**
     * Filter out any more recent unsubscribes in Magento.
     *
     * @param array $localContacts
     * @param array $platformContactMobileNumbers
     *
     * @return array
     * @throws Exception
     */
    private function filterOutRecentSmsUnsubscribers(array $localContacts, array $platformContactMobileNumbers)
    {
        return array_filter(array_map(function ($contact) use ($platformContactMobileNumbers) {
            // get corresponding platform contact
            $contactKey = array_search($contact['mobile_number'], $platformContactMobileNumbers);

            // if there is no last subscribed value, continue with unsubscribe
            if ($contactKey === false || $contact['sms_change_status_at'] === null) {
                return $contact;
            }

            // convert both timestamps to DateTime
            $lastUnsubscribedMagento = new \DateTime(
                $contact['sms_change_status_at'],
                new \DateTimeZone('UTC')
            );
            $utcFromTime = new \DateTime(
                $this->cronFromTimeSetter->getFromTime(),
                new \DateTimeZone('UTC')
            );

            // user recently resubscribed in Magento, do not unsubscribe them
            if ($lastUnsubscribedMagento > $utcFromTime) {
                return null;
            }
            return $contact;
        }, $localContacts));
    }
}
