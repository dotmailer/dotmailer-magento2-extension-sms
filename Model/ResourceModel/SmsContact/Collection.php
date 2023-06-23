<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
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
}
