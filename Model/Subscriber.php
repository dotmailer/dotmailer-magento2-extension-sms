<?php

namespace Dotdigitalgroup\Sms\Model;

use Magento\Framework\Model\AbstractModel;

class Subscriber extends AbstractModel
{
    public const STATUS_SUBSCRIBED = 1;
    public const STATUS_UNSUBSCRIBED = 2;
}
