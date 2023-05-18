<?php

namespace Dotdigitalgroup\Sms\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Return a list of statuses.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('Pending'),
            ],
            [
                'value' => '1',
                'label' => __('In progress'),
            ],
            [
                'value' => '2',
                'label' => __('Delivered'),
            ],
            [
                'value' => '3',
                'label' => __('Failed'),
            ],
            [
                'value' => '4',
                'label' => __('Expired'),
            ],
            [
                'value' => '5',
                'label' => __('Unknown'),
            ]
        ];
    }
}
