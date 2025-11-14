<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Message;

/**
 * CPaaS configuration message data model.
 */
class CpaasConfigMessage
{
    /**
     * @var int
     */
    private int $websiteId;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->websiteId = $data['websiteId'] ?? 0;
    }

    /**
     * Get website ID.
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }
}
