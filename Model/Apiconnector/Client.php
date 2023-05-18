<?php

namespace Dotdigitalgroup\Sms\Model\Apiconnector;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Rest;
use Exception;
use stdClass;

class Client extends Rest
{
    public const REST_CPAAS_MESSAGES_API_URL = 'https://api-cpaas.dotdigital.com/cpaas/messages';
    public const REST_CPAAS_DEDICATED_NUMBERS = 'https://api-cpaas.dotdigital.com/cpaas/sms/dedicatedNumbers';
    public const REST_CPAAS_KEYWORDS = 'https://api-cpaas.dotdigital.com/cpaas/sms/keywords';
    public const REST_CPAAS_SHORTCODES = 'https://api-cpaas.dotdigital.com/cpaas/sms/shortcodes';

    /**
     * Send a single SMS message request.
     *
     * @param mixed $data
     * @return array|stdClass|null
     * @throws Exception
     */
    public function sendSmsSingle($data)
    {
        $this->setUrl(self::REST_CPAAS_MESSAGES_API_URL)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->validationFailures)) {
            $this->addClientLog('SMS send failed')
                ->addClientLog('Validation failures', [
                    'data' => $response->validationFailures,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Retrieves data for a sent message.
     *
     * @param string $messageId
     *
     * @return \stdClass
     * @throws Exception
     */
    public function getMessageByMessageId($messageId)
    {
        $this->setUrl(self::REST_CPAAS_MESSAGES_API_URL . '/' . $messageId)
            ->setVerb('GET');

        $response = $this->execute();

        if (!isset($response->messageId)) {
            $this->addClientLog('Error fetching message by ID', [
                'message_id' => $messageId,
                'response' => (string) $response
            ]);
        }

        return $response;
    }

    /**
     * Send a batch of SMS messages.
     *
     * @param mixed $data
     * @return mixed|null
     * @throws Exception
     */
    public function sendSmsBatch($data)
    {
        $this->setUrl(self::REST_CPAAS_MESSAGES_API_URL . '/batch')
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->message)) {
            $this->addClientLog('SMS send error: ' . $response->message);
        }

        if (isset($response->validationFailures)) {
            $this->addClientLog('SMS send failed')
                ->addClientLog('Validation failures', [
                    'data' => $response->validationFailures,
                ], Logger::DEBUG);
        }

        return $response;
    }

    /**
     * Get a list of dedicated numbers for the account.
     *
     * @return array
     * @throws Exception
     */
    public function getDedicatedNumbers()
    {
        $url = self::REST_CPAAS_DEDICATED_NUMBERS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting account dedicated numbers');
            return [];
        }

        return $response;
    }

    /**
     * Get a list of keywords for the account.
     *
     * @return array
     * @throws Exception
     */
    public function getKeywords()
    {
        $url = self::REST_CPAAS_KEYWORDS;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting account keywords');
            return [];
        }

        return $response;
    }

    /**
     * Get a list of shortcodes for the account.
     *
     * @return array
     * @throws Exception
     */
    public function getShortCodes()
    {
        $url = self::REST_CPAAS_SHORTCODES;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting account shortcodes');
            return [];
        }

        return $response;
    }
}
