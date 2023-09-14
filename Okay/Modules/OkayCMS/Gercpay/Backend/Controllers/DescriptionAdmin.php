<?php


namespace Okay\Modules\OkayCMS\Gercpay\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;

/**
 * Class DescriptionAdmin
 * @package Okay\Modules\OkayCMS\Gercpay\Backend\Controllers
 */
class DescriptionAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->setContent($this->design->fetch('description.tpl'));
    }
}
