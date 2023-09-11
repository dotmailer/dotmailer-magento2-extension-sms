<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Account;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlFactory;
use Magento\Newsletter\Controller\Manage\Index;

class NewsletterManageIndexPlugin
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
     * @var Session
     */
    private $customerSession;

    /**
     * @var Http
     */
    private $response;

    /**
     * @var UrlFactory
     */
    private $urlFactory;

    /**
     * @param Data $dataHelper
     * @param Configuration $config
     * @param Session $customerSession
     * @param Http $response
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        Data $dataHelper,
        Configuration $config,
        Session $customerSession,
        Http $response,
        UrlFactory $urlFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->response = $response;
        $this->urlFactory = $urlFactory;
    }

    /**
     * Around execute.
     *
     * @param Index $subject
     * @param callable $proceed
     *
     * @return callable|void
     * @throws LocalizedException
     */
    public function aroundExecute(
        Index $subject,
        callable $proceed
    ) {
        $websiteId = $this->customerSession->getCustomer()->getWebsiteId();
        $storeId = $this->customerSession->getCustomer()->getStoreId();
        if (!$this->config->isSmsConsentEnabled($storeId) ||
            !$this->dataHelper->isEnabled($websiteId)) {
            return $proceed();
        }

        $this->response->setRedirect(
            $this->urlFactory->create()->getUrl('connector/customer/index')
        );
    }
}
