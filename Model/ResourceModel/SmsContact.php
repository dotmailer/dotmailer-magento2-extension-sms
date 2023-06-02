<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Sms\Model\Subscriber;

class SmsContact extends Contact
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
            ['sms_subscriber_imported' => Subscriber::STATUS_IMPORTED],
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
    public function reset(string $from = null, string $to = null)
    {
        $conn = $this->getConnection();

        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'sms_subscriber_imported = ?' => 1
            ];
        } else {
            $where = ['sms_subscriber_imported = ?' => 1];
        }

        return $conn->update(
            $this->getTable(SchemaInterface::EMAIL_CONTACT_TABLE),
            ['sms_subscriber_imported' => 0],
            $where
        );
    }
}
