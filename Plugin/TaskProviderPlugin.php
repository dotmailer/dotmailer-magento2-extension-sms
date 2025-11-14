<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Sms\Model\Config\TransactionalSms\CpaasConfigManagerFactory;
use Dotdigitalgroup\Sms\Model\SmsStatusManagerFactory;
use Dotdigitalgroup\Email\Console\Command\Provider\TaskProvider;

class TaskProviderPlugin
{
    /**
     * @var SmsStatusManagerFactory
     */
    private $smsStatusManagerFactory;

    /**
     * @var CpaasConfigManagerFactory
     */
    private $cpaasConfigManagerFactory;

    /**
     * @param SmsStatusManagerFactory $smsStatusManagerFactory
     * @param CpaasConfigManagerFactory $cpaasConfigManagerFactory
     */
    public function __construct(
        SmsStatusManagerFactory $smsStatusManagerFactory,
        CpaasConfigManagerFactory $cpaasConfigManagerFactory
    ) {
        $this->smsStatusManagerFactory = $smsStatusManagerFactory;
        $this->cpaasConfigManagerFactory = $cpaasConfigManagerFactory;
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
