<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

class CpaasStatusNotice extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Get element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $websiteId = $this->getRequest()->getParam('website');
        $status = $this->scopeConfig->getValue(
            'transactional_sms/consent/cpaas_profiles_status',
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        if ($status === 'pending') {
            return sprintf(
                '<div class="message message-notice">%s</div>',
                __('CPaaS configuration is pending. Changes will be applied shortly.')
            );
        }

        if ($status === 'error') {
            return sprintf(
                '<div class="message message-error">%s</div>',
                __('CPaaS configuration failed. Please check logs for details.')
            );
        }

        return '';
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
