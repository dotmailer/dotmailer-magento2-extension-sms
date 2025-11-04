<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Variable;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponRequestProcessorFactory;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Resolver implements ResolverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DotdigitalCouponRequestProcessorFactory
     */
    private $dotdigitalCouponRequestProcessorFactory;

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
        'coupon',
        'store_name',
        'store_view_name',
        'website_name'
    ];

    /**
     * @param Logger $logger
     * @param DotdigitalCouponRequestProcessorFactory $dotdigitalCouponRequestProcessorFactory
     * @param Utility $variableUtility
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        DotdigitalCouponRequestProcessorFactory $dotdigitalCouponRequestProcessorFactory,
        Utility $variableUtility,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->dotdigitalCouponRequestProcessorFactory = $dotdigitalCouponRequestProcessorFactory;
        $this->variableUtility = $variableUtility;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $variable, SmsMessageInterface $sms)
    {
        $args = [];
        if (strpos($variable, '|') !== false) {
            $complexVariable = explode('|', $variable);
            $variable = trim($complexVariable[0]);
            $args = $this->variableUtility->getArgsFromComplexVariable(
                trim($complexVariable[1])
            );
        }

        if (!in_array($variable, $this->templateVariables)) {
            return '';
        }

        $method = $this->variableUtility->getMethodFromVariable($variable);
        return (string) $this->$method($sms, $args);
    }

    /**
     * Get email.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string|null
     */
    private function getEmail($sms, array $args = [])
    {
        return $sms->getEmail();
    }

    /**
     * Get first name.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string|null
     */
    private function getFirstName($sms, array $args = [])
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'firstName');
    }

    /**
     * Get last name.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string|null
     */
    private function getLastName($sms, array $args = [])
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'lastName');
    }

    /**
     * Get store name.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreName($sms, array $args = [])
    {
        $groupId = $this->storeManager->getStore($sms->getStoreId())->getStoreGroupId();
        return $this->storeManager->getGroup($groupId)->getName();
    }

    /**
     * Get store view name.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreViewName($sms, array $args = [])
    {
        return $this->storeManager->getStore($sms->getStoreId())->getName();
    }

    /**
     * Get website name.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string
     * @throws LocalizedException
     */
    private function getWebsiteName($sms, array $args = [])
    {
        return $this->storeManager->getWebsite($sms->getWebsiteId())->getName();
    }

    /**
     * Get coupon.
     *
     * @param SmsMessageInterface $sms
     * @param array $args
     *
     * @return string
     * @throws \ErrorException
     */
    private function getCoupon($sms, array $args = [])
    {
        if (!isset($args['rule_id'])) {
            $this->logger->error(
                sprintf(
                    'Coupon rule_id not correctly set in SMS template type %d',
                    $sms->getTypeId()
                )
            );
            return '';
        }
        return $this->dotdigitalCouponRequestProcessorFactory->create()
            ->processCouponRequest([
                'id' => $args['rule_id'],
                'code_email' => $sms->getEmail()
            ])
            ->getCouponCode();
    }
}
