<?php

namespace Dotdigitalgroup\Sms\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Controller\Adminhtml\Run\Reset as CoreResetController;

class Reset extends CoreResetController
{
    /**
     * Access control.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Sms::config';
}
