<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Message;

use Dotdigital\V3\Models\DataFieldCollection;

class MarketingSmsSubscribeData
{
    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int
     */
    private $contactId;

    /**
     * Set website ID.
     *
     * @param int $websiteId The website ID.
     *
     * @return MarketingSmsSubscribeData
     */
    public function setWebsiteId(int $websiteId): MarketingSmsSubscribeData
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * Get website ID.
     *
     * @return int The website ID.
     */
    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    /**
     * Set contact ID.
     *
     * @param int $contactId The contact ID.
     *
     * @return MarketingSmsSubscribeData
     */
    public function setContactId(int $contactId): MarketingSmsSubscribeData
    {
        $this->contactId = $contactId;
        return $this;
    }

    /**
     * Get contact ID.
     *
     * @return int The contact ID.
     */
    public function getContactId(): int
    {
        return $this->contactId;
    }
}
