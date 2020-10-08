<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

class NewOrder extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_NEW_ORDER;

    /**
     * @var int
     */
    protected $smsConfigPath  = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;
}
