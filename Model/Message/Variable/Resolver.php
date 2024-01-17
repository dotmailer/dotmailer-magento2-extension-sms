<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Variable;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Resolver implements ResolverInterface
{
    /**
     * @var Utility
     */
    private $variableUtility;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string[]
     */
    private $templateVariables = [
        'email',
        'first_name',
        'last_name',
        'store_name',
        'store_view_name',
        'website_name'
    ];

    /**
     * @param Utility $variableUtility
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Utility $variableUtility,
        StoreManagerInterface $storeManager
    ) {
        $this->variableUtility = $variableUtility;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $variable, SmsOrderInterface $sms)
    {
        if (!in_array($variable, $this->templateVariables)) {
            return '';
        }

        $method = $this->variableUtility->getMethodFromVariable($variable);
        return (string) $this->$method($sms);
    }

    /**
     * Get email.
     *
     * @param SmsOrderInterface $sms
     * @return string|null
     */
    private function getEmail($sms)
    {
        return $sms->getEmail();
    }

    /**
     * Get first name.
     *
     * @param SmsOrderInterface $sms
     * @return string|null
     */
    private function getFirstName($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'firstName');
    }

    /**
     * Get last name.
     *
     * @param SmsOrderInterface $sms
     * @return string|null
     */
    private function getLastName($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'lastName');
    }

    /**
     * Get store name.
     *
     * @param SmsOrderInterface $sms
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreName($sms)
    {
        $groupId = $this->storeManager->getStore($sms->getStoreId())->getStoreGroupId();
        return $this->storeManager->getGroup($groupId)->getName();
    }

    /**
     * Get store view name.
     *
     * @param SmsOrderInterface $sms
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreViewName($sms)
    {
        return $this->storeManager->getStore($sms->getStoreId())->getName();
    }

    /**
     * Get website name.
     *
     * @param SmsOrderInterface $sms
     * @return string
     * @throws NoSuchEntityException
     */
    private function getWebsiteName($sms)
    {
        return $this->storeManager->getWebsite($sms->getWebsiteId())->getName();
    }
}
