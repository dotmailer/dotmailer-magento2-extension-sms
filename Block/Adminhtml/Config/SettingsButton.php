<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class SettingsButton extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setLabel(__('Edit SMS settings'))
            ->setOnClick("#")
            ->toHtml();
    }

    /**
     * Removes use Default Checkbox
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
