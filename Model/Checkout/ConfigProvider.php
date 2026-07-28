<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationExtensionFactory;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ShippingInformationExtensionFactory
     */
    private $shippingInformationExtensionFactory;

    /**
     * @param ShippingInformationExtensionFactory $shippingInformationExtensionFactory
     */
    public function __construct(
        ShippingInformationExtensionFactory $shippingInformationExtensionFactory
    ) {
        $this->shippingInformationExtensionFactory = $shippingInformationExtensionFactory;
    }

    /**
     * Expose flags indicating whether SMS consent extension attributes are available.
     *
     * These check whether the generated getter methods exist on the ShippingInformationExtension
     * object. If setup:di:compile has not been run, or has not run correctly, the generated extension
     * attribute classes will be absent and the mixins must not include those attributes in the
     * checkout payload — otherwise Magento's ServiceInputProcessor will throw an InputException.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $extensionAttributes = $this->shippingInformationExtensionFactory->create();

        return [
            'ddSmsTransactionalConsentExtensionAttributeAvailable' => method_exists(
                $extensionAttributes,
                'getDdSmsTransactionalConsentCheckbox'
            ),
            'ddSmsMarketingConsentExtensionAttributesAvailable' => method_exists(
                $extensionAttributes,
                'getDdSmsMarketingConsentCheckbox'
            ) && method_exists(
                $extensionAttributes,
                'getDdSmsMarketingConsentTelephone'
            ),
        ];
    }
}
