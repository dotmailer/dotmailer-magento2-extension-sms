<?php

namespace Dotdigitalgroup\Sms\Model\ResourceModel;

use Dotdigitalgroup\Sms\Setup\SchemaInterface as Schema;
use Magento\Framework\Model\ResourceModel\Db\Context;

class SmsOrder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init(Schema::EMAIL_SMS_ORDER_QUEUE_TABLE, 'id');
    }
}
