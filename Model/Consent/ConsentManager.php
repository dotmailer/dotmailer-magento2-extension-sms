<?php

namespace Dotdigitalgroup\Sms\Model\Consent;

use Dotdigitalgroup\Email\Model\Consent;
use Dotdigitalgroup\Email\Model\Consent\ConsentManager as EmailConsentManager;
use Dotdigitalgroup\Email\Model\ConsentFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent as ConsentResource;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\StoreManagerInterface;

class ConsentManager extends EmailConsentManager
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Http $http
     * @param Header $header
     * @param RedirectInterface $redirect
     * @param ConsentFactory $consentFactory
     * @param ConsentResource $consentResource
     * @param Consent $consent
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param StringUtils $stringUtils
     * @param Configuration $config
     */
    public function __construct(
        Http $http,
        Header $header,
        RedirectInterface $redirect,
        ConsentFactory $consentFactory,
        ConsentResource $consentResource,
        Consent $consent,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        StringUtils $stringUtils,
        Configuration $config
    )
    {
        $this->config = $config;
        parent::__construct($http, $header, $redirect, $consentFactory, $consentResource, $consent, $storeManager, $scopeConfig, $stringUtils);
    }

    /**
     * Get consent text for store view.
     *
     * @param string $consentUrl
     * @param string|int $storeId
     * @return string
     */
    public function getConsentTextForStoreView(string $consentUrl, $storeId): string
    {
        return $this->config->getSmsMarketingConsentText($storeId);
    }
}
