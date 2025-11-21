<?php

namespace Dotdigitalgroup\Sms\Model\Apiconnector;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client as EmailApiClient;
use Exception;
use stdClass;

class Client extends EmailApiClient
{
    public const REST_CPAAS_MESSAGES_API_URL = '/cpaas/messages';
    public const REST_CPAAS_DEDICATED_NUMBERS = '/cpaas/sms/dedicatedNumbers';
    public const REST_CPAAS_KEYWORDS = '/cpaas/sms/keywords';
    public const REST_CPAAS_SHORTCODES = '/cpaas/sms/shortcodes';
    public const REST_CPAAS_OPTOUT_RULES = '/cpaas/automation/inboundrules/optout';
    public const REST_CPAAS_PROFILES = '/cpaas/profiles';
    public const REST_CPAAS_PROFILES_OPTIN = '/cpaas/profiles/optin';

    /**
     * Send a single SMS message request.
     *
     * @param mixed $data
     * @return array|stdClass|null
     * @throws Exception
     */
    public function sendSmsSingle($data)
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_MESSAGES_API_URL;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($data);

        $response = $this->execute();

        if (isset($response->validationFailures)) {
            $this->addClientLog('SMS send failed')
                ->addClientLog('Validation failures', [
                    'data' => $response->validationFailures,
                ], Logger::DEBUG);
            throw new \Magento\Framework\Exception\LocalizedException('SMS send failed');
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
        $url = $this->getApiEndpoint() . self::REST_CPAAS_MESSAGES_API_URL . '/' . $messageId;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        if (!isset($response->messageId)) {
            $errorMessage = '';
            if (isset($response->message)) {
                $errorMessage = $response->message;
            } elseif (is_string($response)) {
                $errorMessage = $response;
            }
            $this->addClientLog('Error fetching message by ID', [
                'message_id' => $messageId,
                'error' => $errorMessage
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
        $url = $this->getApiEndpoint() . self::REST_CPAAS_MESSAGES_API_URL . '/batch';
        $this->setUrl($url)
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
        $url = $this->getApiEndpoint() . self::REST_CPAAS_DEDICATED_NUMBERS;
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
        $url = $this->getApiEndpoint() . self::REST_CPAAS_KEYWORDS;
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
        $url = $this->getApiEndpoint() . self::REST_CPAAS_SHORTCODES;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();
        if (isset($response->message)) {
            $this->addClientLog('Error getting account shortcodes');
            return [];
        }

        return $response;
    }

    /**
     * Retrieves a Cpaas opt-out rules configuration list.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOptOutRules()
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_OPTOUT_RULES;
        $this->setUrl($url)
            ->setVerb('GET');

        $response = $this->execute();

        return $this->validateResponse($response);
    }

    /**
     * Create a Cpaas opt-out message rule configuration.
     *
     * @param string $keyword
     *
     * @return stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postOptOutRule($keyword)
    {
        $rule = [
            "channel" => "sms",
            "inbound" => "*",
            "keyword" => $keyword,
            "action" => "optOutChange",
            "actionData" => [
                "opt" => "out"
            ]
        ];

        $url = $this->getApiEndpoint() . self::REST_CPAAS_OPTOUT_RULES;
        $this->setUrl($url)
            ->setVerb('POST')
            ->buildPostBody($rule);

        $response = $this->execute();

        return $this->validateResponse($response);
    }

    /**
     * Delete single Cpaas opt-out message rule configuration.
     *
     * @param string $cpaasOptOutRuleId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteOptOutRule($cpaasOptOutRuleId)
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_OPTOUT_RULES . '/' . $cpaasOptOutRuleId;
        $this->setUrl($url)
            ->setVerb('DELETE');

        $response = $this->execute();

        $this->validateResponse($response);
    }

    /**
     * Retrieves Cpaas profiles with optional filter.
     *
     * @param string $filter
     *
     * @return stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProfiles($filter = '')
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_PROFILES;
        $this->setUrl($url . $filter)
        ->setVerb('GET');

        $response = $this->execute();

        return $this->validateResponse($response);
    }

    /**
     * Updates a Cpaas profile opt-in settings by profile ID.
     *
     * @param string $profileId
     * @param array $payload
     *
     * @return stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateProfileOptIn($profileId, $payload)
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_PROFILES.'/' . $profileId . '/optin';

        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($payload);

        $response = $this->execute();

        return $this->validateResponse($response);
    }

    /**
     * Updates Cpaas profiles default opt-in settings.
     *
     * @param array $payload
     *
     * @return stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateProfilesOptInDefaults($payload)
    {
        $url = $this->getApiEndpoint() . self::REST_CPAAS_PROFILES_OPTIN;

        $this->setUrl($url)
            ->setVerb('PUT')
            ->buildPostBody($payload);

        $response = $this->execute();

        return $this->validateResponse($response);
    }

    /**
     * Check response for defined API errors and http error codes.
     *
     * @param string|array|stdClass $response
     *
     * @return string|array|stdClass
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateResponse($response)
    {
        if (isset($response->message)) {
            $errorMessage = sprintf(
                'API Error: %s',
                $response->message
            );
            $this->addClientLog($errorMessage, [], Logger::ERROR);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errorMessage)
            );
        }

        $clientProperties = $this->expose();
        if (isset($clientProperties['responseInfo']['http_code'])) {
            $httpCode = $clientProperties['responseInfo']['http_code'];

            if ($httpCode < 200 || $httpCode >= 300) {
                $errorMessage = sprintf(
                    'API Error: Request returned HTTP code %s',
                    $httpCode
                );
                $this->addClientLog($errorMessage, ['apiEndpoint' => $this->getApiEndpoint()], Logger::ERROR);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($errorMessage)
                );
            }
        }

        return $response;
    }
}
