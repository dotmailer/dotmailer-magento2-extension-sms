<?php

namespace Dotdigitalgroup\Sms\Model\Sync\Batch;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\ContactCollection as DotdigitalContactCollection;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContactFactory as SmsContactResourceFactory;
use Http\Client\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

class SmsSubscriberBatchProcessor
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var SmsContactResourceFactory
     */
    private $smsContactResourceFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ClientFactory $clientFactory
     * @param ImporterFactory $importerFactory
     * @param SmsContactResourceFactory $smsContactResourceFactory
     * @param Logger $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ClientFactory $clientFactory,
        ImporterFactory $importerFactory,
        SmsContactResourceFactory $smsContactResourceFactory,
        Logger $logger,
        SerializerInterface $serializer
    ) {
        $this->clientFactory = $clientFactory;
        $this->importerFactory = $importerFactory;
        $this->smsContactResourceFactory = $smsContactResourceFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Process batch.
     *
     * @param array $batch
     * @param int $websiteId
     *
     * @return void
     * @throws LocalizedException
     */
    public function process(array $batch, $websiteId)
    {
        if (empty($batch)) {
            return;
        }

        $batchEntityIdentifiers = array_keys($batch);
        $this->logger->info('SMS Subscriber Batch Processor: ' . count($batch));

        try {
            $importId = $this->pushBatch($batch, $websiteId);
            if ($importId) {
                $this->addInProgressBatchToImportTable($batch, $websiteId, $importId);
            }
        } catch (ResponseValidationException | Exception | \Exception $e) {
            $this->logger->debug((string) $e);
            $this->addFailedBatchToImportTable(
                $batch,
                $websiteId,
                $e->getMessage()
            );
        } finally {
            $this->markAsImported($batchEntityIdentifiers);
        }
    }

    /**
     * Push batch to Dotdigital.
     *
     * @param array $batch
     * @param int $websiteId
     *
     * @return string
     * @throws ResponseValidationException
     * @throws Exception
     */
    private function pushBatch(array $batch, $websiteId): string
    {
        $importId = '';

        $contactCollection = new DotdigitalContactCollection(
            $this->resetArrayPointers($batch)
        );
        $contactsResource = $this->clientFactory->create(['data' => ['websiteId' => $websiteId]])->contacts;
        $importResponse = $contactsResource->import($contactCollection);

        if ($importResponse) {
            $importId = $this->getImportIdFromResponse($importResponse);
            if ($importId) {
                $this->logger->info(
                    sprintf('Import id %s pushed to Dotdigital', $importId)
                );
            }
        }

        return $importId;
    }

    /**
     * Reset array pointers.
     *
     * @param array $batch
     * @return array
     */
    private function resetArrayPointers(array $batch): array
    {
        return array_values($batch);
    }

    /**
     * Mark contacts as imported.
     *
     * @param array $batchIdentifiers
     * @return void
     * @throws LocalizedException
     */
    private function markAsImported(array $batchIdentifiers): void
    {
        $this->smsContactResourceFactory->create()
            ->setSmsContactsImportedByIds(
                $batchIdentifiers
            );
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param string|int $websiteId
     * @param string $importId
     *
     * @return void
     */
    private function addInProgressBatchToImportTable(array $batch, $websiteId, string $importId)
    {
        $this->importerFactory->create()
            ->registerQueue(
                SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::IMPORTING,
                $importId
            );
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param string|int $websiteId
     * @param string $message
     *
     * @return void
     */
    private function addFailedBatchToImportTable(array $batch, $websiteId, string $message)
    {
        $this->importerFactory->create()
            ->registerQueue(
                SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::FAILED,
                '',
                $message
            );
    }

    /**
     * Get import id from serialized JSON.
     *
     * @param string $response
     *
     * @return string
     */
    private function getImportIdFromResponse($response)
    {
        try {
            $responseData = $this->serializer->unserialize($response);
            return $responseData['importId'] ?? '';
        } catch (\InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            return '';
        }
    }
}
