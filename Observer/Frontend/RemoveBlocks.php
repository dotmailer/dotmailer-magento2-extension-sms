<?php

namespace Dotdigitalgroup\Sms\Observer\Frontend;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class RemoveBlocks implements ObserverInterface
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $dataHelper
     * @param Configuration $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $dataHelper,
        Configuration $config,
        StoreManagerInterface $storeManager
    ) {
        $this->dataHelper = $dataHelper;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $websiteId = $this->storeManager->getWebsite()->getId();

        if (!$this->config->isSmsConsentEnabled($storeId) ||
            !$this->dataHelper->isEnabled($websiteId)) {
            return;
        }
        $layout = $observer->getLayout();
        $layout->unsetElement('customer-account-navigation-newsletter-subscriptions-link');
        $layout->unsetElement('customer-account-navigation-dd-email-subscriptions');
    }
}
