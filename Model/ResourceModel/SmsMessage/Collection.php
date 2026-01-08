<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage;

use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage as SmsMessageResource;
use Dotdigitalgroup\Sms\Model\SmsMessage as SmsMessageModel;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * SmsMessage Initialization
     */
    public function _construct()
    {
        $this->_init(
            SmsMessageModel::class,
            SmsMessageResource::class
        );
    }
}
