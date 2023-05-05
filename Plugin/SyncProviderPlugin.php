<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Email\Console\Command\Provider\SyncProvider;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;

class SyncProviderPlugin
{
    /**
     * @var SmsSubscriberFactory
     */
    private $smsSubscriberFactory;

    /**
     * @param SmsSubscriberFactory $smsSubscriberFactory
     */
    public function __construct(SmsSubscriberFactory $smsSubscriberFactory)
    {
        $this->smsSubscriberFactory = $smsSubscriberFactory;
    }

    /**
     * Add SMS Subscriber sync to the list of available syncs.
     *
     * @param SyncProvider $syncProvider
     * @param array $additionalSyncs
     * @return array
     */
    public function beforeGetAvailableSyncs(SyncProvider $syncProvider, array $additionalSyncs = [])
    {
        return [
            'additionalSyncs' => $additionalSyncs + get_object_vars($this),
        ];
    }
}
