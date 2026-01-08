<?php

namespace Dotdigitalgroup\Sms\ViewModel;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * TelephoneInputConfig constructor.
     * @param Configuration $moduleConfig
     * @param RequestInterface $request
     * @param Escaper $escaper
     * @param SerializerInterface $serializer
     * @param AssetRepository $assetRepository
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     */
    public function __construct(
        Configuration $moduleConfig,
        RequestInterface $request,
        Escaper $escaper,
        SerializerInterface $serializer,
        AssetRepository $assetRepository,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->request = $request;
        $this->escaper = $escaper;
        $this->serializer = $serializer;
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Get config block for frontend.
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $config  = [
            "nationalMode" => false,
            "strictMode" => true,
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
     * Check is the customer phone number is required.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isRequired(): bool
    {
        return (bool)$this->eavConfig
            ->getAttribute('customer_address', 'telephone')
            ->getIsRequired();
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
