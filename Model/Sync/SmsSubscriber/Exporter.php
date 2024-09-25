<?php

namespace Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Dotdigital\V3\Models\Contact as DotdigitalContact;

class Exporter
{
    public const DATA_KEYS = [
        'firstname',
        'lastname' ,
        'store_name' ,
        'store_name_additional',
        'website_name'
    ];

    /**
     * @var array $fieldMap
     */
    private $fieldMap = [];

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * Mutate the data to be exported
     *
     * @param Collection $smsSubscribers
     * @param WebsiteInterface|null $website
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|\Exception
     */
    public function export(Collection $smsSubscribers, ?WebsiteInterface $website = null): array
    {
        $exportedData = [];
        $smsAddressBookListId = $this->configuration->getListId($website->getId());

        foreach ($smsSubscribers as $smsSubscriber) {
            $contact = $this->prepareContact($smsSubscriber);
            $contact->setLists([$smsAddressBookListId]);
            $exportedData["{$smsSubscriber->getEmailContactId()}"] = $contact;
        }

        return $exportedData;
    }

    /**
     * Export a single subscriber
     *
     * @param SmsContact $smsSubscriber
     * @return DotdigitalContact
     * @throws \Exception
     */
    public function prepareContact(SmsContact $smsSubscriber): DotdigitalContact
    {
        $contact = new DotdigitalContact([
            'matchIdentifier' => 'email'
        ]);
        $contact->setIdentifiers([
            'email' => $smsSubscriber->getEmail(),
            'mobileNumber' => $smsSubscriber->getMobileNumber()
        ]);
        $contact->setDataFields(
            $this->mapFields($smsSubscriber->getData())
        );

        return $contact;
    }

    /**
     * Map and filter data fields the data to be exported
     *
     * @param array $data
     * @return array
     */
    private function mapFields(array $data): array
    {
        $filteredData = array_filter($data, function ($key) {
            return in_array($key, self::DATA_KEYS);
        }, ARRAY_FILTER_USE_KEY);

        $mappedData = array_map(
            function ($key, $value) {
                if (in_array($key, array_keys($this->fieldMap))
                    && !empty($this->fieldMap[$key])
                ) {
                    return [$this->fieldMap[$key] => $value];
                }
                return [];
            },
            array_keys($filteredData),
            array_values($filteredData)
        );

        return array_merge(...$mappedData);
    }

    /**
     * Set the key mapping for the data to be exported
     *
     * @param array $fieldMap
     * @return $this
     */
    public function setFieldMapping(array $fieldMap): Exporter
    {
        $this->fieldMap = [
            ...$fieldMap,
            ...$this->fieldMap
        ];
        return $this;
    }
}
