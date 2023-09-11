<?php

namespace Dotdigitalgroup\Sms\Block\Customer\Account\Link;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;

/**
 * @api
 */
class Subscriptions extends Current
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param Data $dataHelper
     * @param Configuration $config
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        Data $dataHelper,
        Configuration $config
    ) {
        $this->dataHelper = $dataHelper;
        $this->config = $config;
        parent::__construct($context, $defaultPath);
    }

    /**
     * ToHTML method.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _toHtml()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $websiteId = $this->_storeManager->getWebsite()->getId();

        if (!$this->config->isSmsConsentEnabled($storeId) ||
            !$this->dataHelper->isEnabled($websiteId)) {
            return '';
        }
        return parent::_toHtml();
    }
}
