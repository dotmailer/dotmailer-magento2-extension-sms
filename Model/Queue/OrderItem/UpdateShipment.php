<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

class UpdateShipment extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT;

    /**
     * @var string
     */
    protected $smsConfigPath = ConfigInterface::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED;
}
