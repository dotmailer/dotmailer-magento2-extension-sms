<?php

namespace Dotdigitalgroup\Sms\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
class SubscriberStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '1',
                'label' => __('Subscribed'),
            ],
            [
                'value' => '2',
                'label' => __('Unsubscribed'),
            ]
        ];
    }
}
