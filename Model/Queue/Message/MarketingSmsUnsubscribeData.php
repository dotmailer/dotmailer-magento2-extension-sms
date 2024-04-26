<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Message;

class MarketingSmsUnsubscribeData
{
    /**
     * @var string
     */
    private $websiteId;

    /**
     * @var string
     */
    private $email;

    /**
     * Set website ID.
     *
     * @param string $websiteId The website ID.
     *
     * @return void
     */
    public function setWebsiteId(string $websiteId): void
    {
        $this->websiteId = $websiteId;
    }

    /**
     * Get website ID.
     *
     * @return string The website ID.
     */
    public function getWebsiteId(): string
    {
        return $this->websiteId;
    }

    /**
     * Set email.
     *
     * @param string $email The email.
     *
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get email.
     *
     * @return string The email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
