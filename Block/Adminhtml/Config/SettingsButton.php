<?php

namespace Dotdigitalgroup\Sms\Block\Adminhtml\Config;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Dotdigitalgroup\Email\Helper\OauthValidator;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class SettingsButton extends \Magento\Config\Block\System\Config\Form\Field
{
    const SMS_SETTINGS_URL = 'account/settings/sms';

    /**
     * @var EmailConfig
     */
    private $emailConfig;

    /**
     * @var OauthValidator
     */
    private $oauthValidator;

    /**
     * SettingsButton constructor.
     * @param Context $context
     * @param OauthValidator $oauthValidator
     * @param EmailConfig $emailConfig
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        OauthValidator $oauthValidator,
        EmailConfig $emailConfig,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->emailConfig = $emailConfig;
        $this->oauthValidator = $oauthValidator;
        parent::__construct($context, $data, $secureRenderer);
    }

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
            ->setOnClick(sprintf("window.open('%s','_blank')", $this->getButtonUrl()))
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

    /**
     * @return string
     */
    private function getButtonUrl()
    {
        return $this->oauthValidator->createAuthorisedEcUrl($this->getSmsSettingsUrl(), 'false');
    }

    /**
     * @return string
     */
    private function getSmsSettingsUrl()
    {
        return $this->emailConfig->getRegionAwarePortalUrl() . self::SMS_SETTINGS_URL;
    }
}
