<?php

namespace Dotdigitalgroup\Sms\Model\Sync\Importer\Type\Contact;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\ContactSenderStrategyFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Exception;

class Bulk extends AbstractItemSyncer
{
    /**
     * @var V3ItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ContactSenderStrategyFactory
     */
    private $contactSenderStrategyFactory;

    /**
     * Bulk constructor.
     * @param V3ItemPostProcessorFactory $postProcessor
     * @param SerializerInterface $serializer
     * @param ContactSenderStrategyFactory $contactSenderStrategyFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        V3ItemPostProcessorFactory $postProcessor,
        SerializerInterface $serializer,
        ContactSenderStrategyFactory $contactSenderStrategyFactory,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        $this->contactSenderStrategyFactory = $contactSenderStrategyFactory;
        parent::__construct($logger, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     * @return string
     * @throws Exception
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());
        foreach ($importData as $key => $data) {
            $contact = new SdkContact($data);
            $importData[$key] = $contact;
        }

        return $this->contactSenderStrategyFactory->create()
            ->setBatch($importData)
            ->setWebsiteId($item->getWebsiteId())
            ->process();
    }
}
