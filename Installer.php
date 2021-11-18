<?php

/*
 * Fresns (https://fresns.cn)
 * Copyright (C) 2021-Present 唐杰
 * Released under the Apache-2.0 License.
 */

namespace App\Plugins\ImageX;

use App\Http\Center\Base\BaseInstaller;
use App\Http\Center\Helper\CmdRpcHelper;
use App\Http\FresnsCmd\FresnsCrontabPlugin;
use App\Http\FresnsCmd\FresnsCrontabPluginConfig;

class Installer extends BaseInstaller
{
    protected $pluginConfig;

    public function __construct()
    {
        $this->pluginConfig = new PluginConfig();
    }

    // 插件安装
    public function install()
    {
        parent::install();
    }

    /// 插件卸载
    public function uninstall()
    {
        parent::uninstall();
    }
}
