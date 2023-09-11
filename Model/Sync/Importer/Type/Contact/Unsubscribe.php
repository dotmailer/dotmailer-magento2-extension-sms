<?php

namespace Dotdigitalgroup\Sms\Model\Sync\Importer\Type\Contact;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class Unsubscribe extends AbstractItemSyncer
{
    /**
     * @var SingleItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * @var Configuration
     */
    private $smsConfig;

    /**
     * Update constructor.
     *
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param Logger $logger
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Configuration $smsConfig
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        Logger $logger,
        SingleItemPostProcessorFactory $postProcessor,
        Configuration $smsConfig,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->smsConfig = $smsConfig;
        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $logger, $data);
    }

    /**
     * Process an SMS_Subscriber / Subscriber_Unsubscribe import row.
     *
     * @param mixed $item
     *
     * @return \stdClass|void
     * @throws LocalizedException
     */
    protected function process($item)
    {
        $websiteId = $item->getWebsiteId();
        $importData = $this->serializer->unserialize($item->getImportData());
        $contactId = $importData['id'] ?? null;
        $email = $importData['email'] ?? null;

        if (!$contactId) {
            if (!$email) {
                $result = new \stdClass();
                $result->message = 'Missing email address';
                return $result;
            }
            $result = $this->client->getContactByEmail($email);
            if (!isset($result->id)) {
                return $result;
            }
            $contactId = $result->id;
        }

        $this->client->deleteAddressBookContact(
            $this->smsConfig->getListId($websiteId),
            $contactId
        );
    }
}
