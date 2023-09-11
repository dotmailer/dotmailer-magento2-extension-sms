<?php

namespace Dotdigitalgroup\Sms\Plugin;

use Dotdigitalgroup\Email\Model\Connector\Module;

class ModulePlugin
{
    public const MODULE_NAME = 'Dotdigitalgroup_Sms';
    public const MODULE_DESCRIPTION = 'Dotdigital SMS for Magento 2';

    /**
     * @var Module
     */
    private $module;

    /**
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Add SMS module to the list of active modules.
     *
     * @param Module $module
     * @param array $modules
     * @return array
     */
    public function beforeFetchActiveModules(Module $module, array $modules = [])
    {
        $modules[] = [
            'name' => self::MODULE_DESCRIPTION,
            'version' => $this->module->getModuleVersion(self::MODULE_NAME)
        ];
        return [
            $modules
        ];
    }
}
