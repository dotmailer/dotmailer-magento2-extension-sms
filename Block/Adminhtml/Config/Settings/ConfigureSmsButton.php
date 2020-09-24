<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config\Settings;

class ConfigureSmsButton extends \Magento\Config\Block\System\Config\Form\Field
{
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setLabel(__('Manage account'))
            ->setOnClick("#")
            ->toHtml();
    }
}
