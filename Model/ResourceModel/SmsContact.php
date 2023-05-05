<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
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
}
