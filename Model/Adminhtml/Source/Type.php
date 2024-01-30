<?php

namespace Dotdigitalgroup\Sms\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Type implements OptionSourceInterface
{
    /**
     * Return a list of types.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '1',
                'label' => __('New Order'),
            ],
            [
                'value' => '2',
                'label' => __('Order Update'),
            ],
            [
                'value' => '3',
                'label' => __('New Shipment'),
            ],
            [
                'value' => '4',
                'label' => __('Shipment Update'),
            ],
            [
                'value' => '5',
                'label' => __('New Credit Memo'),
            ],
            [
                'value' => '6',
                'label' => __('SMS Sign-Up'),
            ],
            [
                'value' => '7',
                'label' => __('New Account SMS Sign-Up'),
            ]
        ];
    }
}
