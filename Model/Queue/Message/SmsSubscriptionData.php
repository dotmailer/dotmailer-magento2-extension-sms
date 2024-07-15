<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Message;

class SmsSubscriptionData
{
    public const TYPE_SUBSCRIBE = 'subscribe';
    public const TYPE_UNSUBSCRIBE = 'unsubscribe';

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int
     */
    private $contactId;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $type;

    /**
     * Set website ID.
     *
     * @param int $websiteId The website ID.
     *
     * @return SmsSubscriptionData
     */
    public function setWebsiteId(int $websiteId): SmsSubscriptionData
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
     * @return SmsSubscriptionData
     */
    public function setContactId(int $contactId): SmsSubscriptionData
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
        return $this->contactId ?? 0;
    }

    /**
     * Set email.
     *
     * @param string $email The email.
     *
     * @return SmsSubscriptionData
     */
    public function setEmail(string $email): SmsSubscriptionData
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email.
     *
     * @return string The email.
     */
    public function getEmail(): string
    {
        return $this->email ?? '';
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return SmsSubscriptionData
     */
    public function setType(string $type): SmsSubscriptionData
    {
        if (!in_array($type, [self::TYPE_SUBSCRIBE, self::TYPE_UNSUBSCRIBE])) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
