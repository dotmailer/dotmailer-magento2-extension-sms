<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Helper\Data as EmailHelper;

class Account
{
    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @var bool
     */
    private $canSendSms;

    /**
     * Account constructor.
     * @param EmailHelper $emailHelper
     */
    public function __construct(
        EmailHelper $emailHelper
    ) {
        $this->emailHelper = $emailHelper;
    }

    /**
     * Can send sms in current scope.
     *
     * @return bool
     * @throws \Exception
     */
    public function canSendSmsInCurrentScope()
    {
        if (isset($this->canSendSms)) {
            return $this->canSendSms;
        }
        $currentWebsite = $this->emailHelper->getWebsiteForSelectedScopeInAdmin();
        $accountInfo = $this->emailHelper->getWebsiteApiClient($currentWebsite->getId())
            ->getAccountInfo();

        $this->canSendSms = $this->accountCanSendSms($accountInfo);
        return $this->canSendSms;
    }

    /**
     * Account can send sms.
     *
     * @param \StdClass $accountInfo
     * @return bool
     */
    private function accountCanSendSms($accountInfo)
    {
        if (isset($accountInfo->properties)) {
            foreach ($accountInfo->properties as $property) {
                if ($this->IsUsingSMSPAYG($property) || $this->isDDGAdmin($property)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Is using sms PAYG.
     *
     * @param \StdClass $property
     * @return bool
     */
    private function isUsingSMSPAYG($property)
    {
        return $property->name === 'IsUsingSMSPAYG' && $property->value === 'True';
    }

    /**
     * Is DDG admin.
     *
     * @param \StdClass $property
     * @return bool
     */
    private function isDDGAdmin($property)
    {
        return $property->name === 'MainEmail' && (strpos($property->value, 'dotdigital.com') !== false);
    }
}
