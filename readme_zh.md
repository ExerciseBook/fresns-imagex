# 火山引擎 ImageX Fresns 集成插件

[English Edition](readme.md)

## 插件特色

1. [基础] 文件存储。您可以在 ImageX 中保存任何您喜欢的内容
2. [强大] 图像处理。您可以压缩图片以加快网页加载速度。
3. [安全] 地址鉴权。防止用户恶意外链您的文件。

## 安装配置

1. 安装插件  
   使用标识名安装：`ImageX`  
   使用指令安装：`php artisan market:require ImageX`
2. 安装成功后点击启用
3. 在系统——存储配置中配置

| 模块	      | 配置	         | 解释                                                                                                                                                           | 例子                         |
|----------|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------|
| 存储配置     | 	存储服务商	     | 选择 `ImageX Integration`                                                                                                                                      | `ImageX Integration`       | 
|          | Secret ID	  | 从[VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)获取到的 `Access Key ID`                                                                         | `AKbalabala`               |
|          | Secret Key	 | 从[VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)获取到的 `Secret Access Key`                                                                     | `SuiJiDeWenBen`            |
|          | 存储配置名称	     | 从[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)获取到的 `服务 ID` 以及从[火山引擎 BAF](https://console.volcengine.com/baf/my_app/) 获得到的 AppID | `IMGBianHao,123456`        | 
|          | 存储配置地域      | 	从[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)获取到的 `服务地区` (1)                                                                  | `cn-north-1`               |
|          | 存储配置域名	     | 在[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)中设置的 `域名`                                                                         | `https://example.com`      |
|          | 文件系统磁盘	     | 选择 `remote`                                                                                                                                                  | `remote`                   |
| 功能配置     | 	防盗链 Key	   | url 鉴权密钥 (2)                                                                                                                                                 | `suijideWenben`            |
|          | 防盗链签名有效期	   | 请保证该值小于您在（2）配置页面中配置的值                                                                                                                                        |                            |
| 图片处理功能配置 | 图片处理位置      | 	选择 `end`                                                                                                                                                    | `end`                      |
|          | 本节其他空       | 	以开头 `~tplv-` ，以文件扩展名结尾的 ImageX 模板                                                                                                                           | `~tplv-Service0ID-t1.avif` |
| 视频功能	    | 视频转码参数      | 以开头 `~tplv-` ，以文件扩展名结尾的 ImageX 模板                                                                                                                            |                            |
|          | 视频水印参数      | 没用                                                                                                                                                           |                            |
|          | 视频截图参数      | 以开头 `~tplv-` ，以文件扩展名结尾的 ImageX 模板                                                                                                                            |                            |
|          | 视频转动图参数     | 不推荐使用                                                                                                                                                        |                            |
| 音频功能	    | `-`	        | `不支持` ImageX 不提供任何音频处理功能，但是可以将音频文件存储在 ImageX 中                                                                                                               |                            |
| 文档功能	    | `-`	        | `支持` ImageX 可以存储任何你喜欢的文件                                                                                                                                     |                            |

(1) 该值一般是 `cn-north-1`、`ap-singapore-1`或`us-east-1`之一。

(2)
配置页面地址是 `https://console.volcengine.com/imagex/service_manage/http_config/{SERVICE_ID}/{DOMAIN}`
，例如 `https://console.volcengine.com/imagex/service_manage/http_config/dQw4w9WgXcQ/example.com`
。本插件只支持`鉴权方式 B`。
