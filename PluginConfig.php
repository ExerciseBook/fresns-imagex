<?php

namespace App\Plugins\ImageX;

use App\Http\Center\Base\BasePluginConfig;

class PluginConfig extends BasePluginConfig
{
    public $type = 2; //1.网站引擎 2.扩展插件 3.移动应用 4.控制面板 5.主题模板
    public $uniKey = "ImageX";
    public $name = 'ImageX 存储';
    public $description = "ImageX 存储扩展";
    public $author = "Eric_Lian";
    public $authorLink = "https://github.com/ExerciseBook";
    public $currVersion = '1.0';
    public $currVersionInt = 1;

    // 插件默认命令字, 任何插件必须要要有
    public const CMD_DEFAULT = 'ericlian_imagex_default';

    // 插件命令字回调映射
    CONST FRESNS_CMD_HANDLE_MAP = [
        self::CMD_DEFAULT => 'defaultHandler',
    ];
}
