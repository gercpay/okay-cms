<?php


namespace Okay\Modules\OkayCMS\Gercpay\Init;

use Okay\Core\Modules\AbstractInit;

/**
 * Class Init
 * @package Okay\Modules\OkayCMS\Gercpay\Init
 */
class Init extends AbstractInit
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        $this->setModuleType(MODULE_TYPE_PAYMENT);
        $this->setBackendMainController('DescriptionAdmin');
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        $this->addPermission('okaycms__gercpay');

        $this->registerBackendController('DescriptionAdmin');
        $this->addBackendControllerPermission('DescriptionAdmin', 'okaycms__gercpay');
    }
}
