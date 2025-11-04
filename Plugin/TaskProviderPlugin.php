<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Sms\Model\SmsStatusManagerFactory;
use Dotdigitalgroup\Email\Console\Command\Provider\TaskProvider;

class TaskProviderPlugin
{
    /**
     * @var SmsStatusManagerFactory
     */
    private $smsStatusManagerFactory;

    /**
     * @param SmsStatusManagerFactory $smsStatusManagerFactory
     */
    public function __construct(
        SmsStatusManagerFactory $smsStatusManagerFactory
    ) {
        $this->smsStatusManagerFactory = $smsStatusManagerFactory;
    }

    /**
     * Add SMS tasks to the list of available tasks.
     *
     * @param TaskProvider $taskProvider
     * @param array $additionalSyncs
     * @return array
     */
    public function beforeGetAvailableTasks(TaskProvider $taskProvider, array $additionalSyncs = [])
    {
        return [
            'additionalSyncs' => $additionalSyncs + get_object_vars($this),
        ];
    }
}
