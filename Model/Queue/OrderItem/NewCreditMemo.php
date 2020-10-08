<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

class NewCreditMemo extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_NEW_CREDIT_MEMO;

    /**
     * @var int
     */
    protected $smsConfigPath  = ConfigInterface::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED;
}
