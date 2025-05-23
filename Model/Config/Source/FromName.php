<?php

namespace Dotdigitalgroup\Sms\Model\Config\Source;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;

class FromName implements \Magento\Framework\Option\ArrayInterface
{
    public const SHARED_POOL_NUMBER = 'shared_pool_number';

    /**
     * @note this value is hard dependent to transactional_sms/sms_settings/alphanumeric_from_name
     */
    public const ALPHANUMERIC_NUMBER = 'alphanumeric_number';

    /**
     * @var SmsClientFactory
     */
    private $smsClientFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * FromName constructor.
     *
     * @param SmsClientFactory $smsClientFactory
     * @param Data $helper
     */
    public function __construct(
        SmsClientFactory $smsClientFactory,
        Data $helper
    ) {
        $this->smsClientFactory = $smsClientFactory;
        $this->helper = $helper;
    }

    /**
     * Get options.
     *
     * @return array
     * @throws \Exception
     */
    public function toOptionArray()
    {
        $fields[] = ['value' => self::SHARED_POOL_NUMBER, 'label' => 'Shared pool number'];
        $fields[] = ['value' => self::ALPHANUMERIC_NUMBER, 'label' => 'Alphanumeric from name'];

        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        $client = $this->smsClientFactory
            ->create($website->getId());

        foreach ($client->getDedicatedNumbers() as $dedicatedNumber) {
            $fields[] = ['value' => $dedicatedNumber->number, 'label' => $dedicatedNumber->number];
        }

        foreach ($client->getShortCodes() as $shortCode) {
            $fields[] = ['value' => $shortCode->number, 'label' => $shortCode->number];
        }

        return $fields;
    }
}
