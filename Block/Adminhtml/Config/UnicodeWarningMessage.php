<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class UnicodeWarningMessage extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Sms::unicode_detection_message.phtml';

    /**
     * Render element value.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
