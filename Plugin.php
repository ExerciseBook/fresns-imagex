<?php

namespace App\Plugins\ImageX;

use App\Http\Center\Base\BasePlugin;
use App\Http\Center\Common\LogService;

class Plugin extends BasePlugin
{
    function defaultHandler($input) {
        LogService::info("插件处理开始: ", $input);

        $data = [
            'time'  => date("Y-m-d H:i:s", time()),
        ];

        LogService::info("插件处理完成: ", $input);

        return $this->pluginSuccess($data);
    }
}
