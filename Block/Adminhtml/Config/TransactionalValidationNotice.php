<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Transactional SMS validation notice block.
 *
 * Block for displaying transactional SMS validation notice in admin config.
 */
class TransactionalValidationNotice extends Field
{
    /**
     * Get element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return sprintf(
            '<div class="message message-warning">
                <strong>%s</strong><br/>
                %s
            </div>',
            __('SMS Phone Number Validation Required'),
            __(
                'Phone number validation must be enabled to use the transactional SMS feature for US numbers. ' .
                'Please enable validation in the Transactional SMS Settings section.'
            )
        );
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
