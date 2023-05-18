<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class FrontendAssets extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Sms::frontend_assets.phtml';

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
