<?php

namespace Dotdigitalgroup\Sms\ViewModel;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class TelephoneInputConfig implements ArgumentInterface
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * TelephoneInputConfig constructor.
     * @param Configuration $moduleConfig
     * @param RequestInterface $request
     * @param Escaper $escaper
     * @param SerializerInterface $serializer
     * @param AssetRepository $assetRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $moduleConfig,
        RequestInterface $request,
        Escaper $escaper,
        SerializerInterface $serializer,
        AssetRepository $assetRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->request = $request;
        $this->escaper = $escaper;
        $this->serializer = $serializer;
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Get config block for frontend.
     *
     * @return bool|string
     */
    public function getConfig()
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $config  = [
            "nationalMode" => false,
            "utilsScript"  => $this->getViewFileUrl('Dotdigitalgroup_Sms::js/utils.js'),
            "preferredCountries" => [$this->moduleConfig->getPreferredCountry($websiteId)]
        ];

        if ($this->moduleConfig->getAllowedCountries($websiteId)) {
            $config["onlyCountries"] = array_map(function ($countryCode) {
                return $this->escaper->escapeJs($countryCode);
            }, explode(",", $this->moduleConfig->getAllowedCountries($websiteId)));
        }

        return $this->serializer->serialize($config);
    }
    
    /**
     * Get path for script.
     *
     * @param string $fileId
     * @return string
     */
    private function getViewFileUrl($fileId)
    {
        return $this->assetRepository->getUrlWithParams(
            $fileId,
            ['_secure' => $this->request->isSecure()]
        );
    }
}
