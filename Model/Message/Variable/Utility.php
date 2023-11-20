<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Variable;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Utility
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Utility constructor.
     *
     * @param Logger $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Transform a variable like 'first_name' into the method name 'getFirstName'.
     *
     * @param string $variable
     * @return string
     */
    public function getMethodFromVariable($variable)
    {
        return 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $variable)));
    }

    /**
     * Get additional data by key.
     *
     * @param SmsOrderInterface $sms
     * @param string $key
     * @return string
     */
    public function getAdditionalDataByKey($sms, $key)
    {
        try {
            $additionalData = $this->serializer->unserialize(
                $sms->getAdditionalData()
            );
            return $additionalData[$key] ?? '';
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(
                'Could not unserialize ' . $key . ' for SMS id ' . $sms->getId(),
                [(string) $e]
            );
            return '';
        }
    }
}
