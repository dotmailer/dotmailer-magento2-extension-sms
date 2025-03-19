<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Framework\Exception\LocalizedException;

class SmsContact extends ContactResource
{
    /**
     * Set imported by ids.
     *
     * @param array $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSmsContactsImportedByIds(array $ids)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['sms_subscriber_imported' => Contact::EMAIL_CONTACT_IMPORTED],
            [
                "email_contact_id IN (?)" => $ids
            ]
        );
    }

    /**
     * Reset imported SMS subscribers.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function reset(?string $from = null, ?string $to = null)
    {
        $conn = $this->getConnection();

        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'sms_subscriber_imported = ?' => Contact::EMAIL_CONTACT_IMPORTED
            ];
        } else {
            $where = ['sms_subscriber_imported = ?' => Contact::EMAIL_CONTACT_IMPORTED];
        }

        return $conn->update(
            $this->getTable(SchemaInterface::EMAIL_CONTACT_TABLE),
            ['sms_subscriber_imported' => Contact::EMAIL_CONTACT_NOT_IMPORTED],
            $where
        );
    }

    /**
     * Subscribe SMS contacts.
     *
     * @param array $mobileNumbers
     * @param array $websiteIds
     *
     * @return int
     * @throws LocalizedException
     * @throws \Exception
     */
    public function subscribeByWebsite(array $mobileNumbers, array $websiteIds)
    {
        if (!empty($mobileNumbers)) {
            $write = $this->getConnection();
            $now = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->format("Y-m-d H:i:s");

            return $write->update(
                $this->getMainTable(),
                [
                    'sms_subscriber_status' => Subscriber::STATUS_SUBSCRIBED,
                    'sms_subscriber_imported' => Contact::EMAIL_CONTACT_NOT_IMPORTED,
                    'sms_change_status_at' => $now,
                    'updated_at' => $now
                ],
                [
                    "mobile_number IN (?)" => $mobileNumbers,
                    "website_id IN (?)" => $websiteIds
                ]
            );
        }

        return 0;
    }

    /**
     * Unsubscribe SMS contacts.
     *
     * @param array $mobileNumbers
     * @param array $websiteIds
     *
     * @return int
     * @throws LocalizedException
     * @throws \Exception
     */
    public function unsubscribeByWebsite(array $mobileNumbers, array $websiteIds)
    {
        if (!empty($mobileNumbers)) {
            $write = $this->getConnection();
            $now = (new \DateTime('now', new \DateTimeZone('UTC')))
                ->format("Y-m-d H:i:s");

            return $write->update(
                $this->getMainTable(),
                [
                    'sms_subscriber_status' => Subscriber::STATUS_UNSUBSCRIBED,
                    'sms_change_status_at' => $now,
                    'updated_at' => $now
                ],
                [
                    "mobile_number IN (?)" => $mobileNumbers,
                    "website_id IN (?)" => $websiteIds
                ]
            );
        }

        return 0;
    }
}
