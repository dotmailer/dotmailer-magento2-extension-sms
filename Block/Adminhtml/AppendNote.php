<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml;

use Magento\Framework\Data\Form\Element\AbstractElement;

class AppendNote extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var AbstractElement
     */
    private $element;

    /**
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Sms::append_note.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->element = $element;
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->element;
    }
}
