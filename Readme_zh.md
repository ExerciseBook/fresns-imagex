# 火山引擎ImageX-用于Fresns的火山引擎云存储和 ImageX 图片处理插件
## 插件特色
1. [基本] 文件存储。您几乎可以在 ImageX 中保存任何您喜欢的内容
2. [强大] 图像处理。您可以更改图像处理以节约带宽。
3. [安全] 网址签名。没有您网站的正确签名，访客无法获取您的文件。

## 安装配置
1. 安装插件，使用标识名安装：ImageX 或 使用指令安装： php artisan market:require ImageX
2. 安装成功后点击启用
3. 在系统——存储配置中配置

| 部分	| 配置	| 解释 | 
| ----------------- | ------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| 存储配置:| 	存储服务商	| 选择`ImageX Integration`| 
|  | Secret ID	| 访问ID 在[VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)获取| 
|  | Secret Key	| 访问密钥 在[VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)获取| 
|  | 存储配置名称	| 存储桶ID 在[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)获取| 
|  | 存储配置地域| 	存储桶服务区域 在[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)获取 (1)| 
|  | 存储配置域名	| 在[VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)中设置的域名| 
|  | 文件系统磁盘	| 选择`remote`| 
| 功能配置: | 	防盗链 Key	|  url签名密钥 (2)| 
|  | 防盗链签名有效期	|  保持该值小于（2）配置页面中的值| 
|  图片处理功能配置:|  图片处理位置| 	选择`end`|  
|  | 本节其他空白	以开头`~tplv-`，以文件扩展名结尾|  
|  视频功能	|  `WIP`|  	`WIP`
|  音频功能	|  `-`	|  `不支持` ImageX 不提供任何音频处理功能，但是可以将音频文件存储在 ImageX 中|  
|  文档功能	|  `-`	|  `支持` ImageX 可以存储任何你喜欢的文件| 


(1) 该值是cn-north-1, ap-singapore-1 或us-east-1之一。

(2)配置页面https://console.volcengine.com/imagex/service_manage/http_config/{SERVICE_ID}/{DOMAIN} ，例如https://console.volcengine.com/imagex/service_manage/http_config/dQw4w9WgXcQ/example.com 。本插件只支持方式B。
