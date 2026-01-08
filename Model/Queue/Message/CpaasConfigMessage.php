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
     * Set website ID.
     *
     * @param int $websiteId
     * @return CpaasConfigMessage
     */
    public function setWebsiteId(int $websiteId): CpaasConfigMessage
    {
        $this->websiteId = $websiteId;
        return $this;
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
