<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact as SmsContactResource;
use Dotdigitalgroup\Sms\Model\SmsContact;

class Collection extends ContactCollection
{
    /**
     * Overload ContactCollection to return SmsContact models.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            SmsContact::class,
            SmsContactResource::class
        );
    }

    /**
     * Get current SMS subscribed contact records.
     *
     * @param array $mobileNumbers
     * @param array $websiteIds
     *
     * @return array
     */
    public function getSmsSubscribedContactsWithChangeStatusAtDate(array $mobileNumbers, $websiteIds)
    {
        return $this
            ->addFieldToSelect([
                'mobile_number',
                'sms_change_status_at'
            ])
            ->addFieldToFilter('mobile_number', ['in' => $mobileNumbers])
            ->addFieldToFilter('website_id', ['in' => $websiteIds])
            ->addFieldToFilter('sms_subscriber_status', Subscriber::STATUS_SUBSCRIBED)
            ->getData();
    }

    /**
     * Get current SMS unsubscribed contact records.
     *
     * @param array $mobileNumbers
     * @param array $websiteIds
     *
     * @return array
     */
    public function getSmsUnsubscribedContactsWithChangeStatusAtDate(array $mobileNumbers, $websiteIds)
    {
        return $this
            ->addFieldToSelect([
                'mobile_number',
                'sms_change_status_at'
            ])
            ->addFieldToFilter('mobile_number', ['in' => $mobileNumbers])
            ->addFieldToFilter('website_id', ['in' => $websiteIds])
            ->addFieldToFilter('sms_subscriber_status', Subscriber::STATUS_UNSUBSCRIBED)
            ->getData();
    }
}
