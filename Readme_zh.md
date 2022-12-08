用于 Fresns 的 VolcEngine ImageX 集成
特征
[基本] 文件存储。您几乎可以在 ImageX 中保存任何您喜欢的内容
[功能强大] 图像处理。您可以更改图像处理以优化带宽。
[安全] 网址签名。没有您网站的正确签名，访客无法获取您的文件。
配置
部分	配置	意义
服务器配置	服务提供者	调成ImageX Integration
密码	您的访问密钥 ID 从VolcEngine IAM获取
密钥	您的秘密访问密钥从VolcEngine IAM获取
桶名	您的服务 ID 从VolcEngine ImageX获得
桶区	您的服务区域来自VolcEngine ImageX (1)
桶域名	您在VolcEngine ImageX中设置的服务域
文件系统磁盘	调成remote
功能配置	反链接密钥	您的 url 签名秘密 (2)
签到有效分钟数	保持该值小于提到的页面中设置的值Anti Link Key。
影像功能	图像处理位置	设置为后缀填充
本节其他空白	以开头~tplv-，以文件扩展名结尾
视频功能	WIP	WIP
音频功能	-	Not supported. ImageX 不提供任何音频处理功能。但是您可以将音频文件存储在 ImageX 中。
文档功能	-	Supported. ImageX 可以存储任何你喜欢的文件。
(1) 该值是cn-north-1, ap-singapore-1,之一us-east-1

(2)配置页面https://console.volcengine.com/imagex/service_manage/http_config/{SERVICE_ID}/{DOMAIN}，例如https://console.volcengine.com/imagex/service_manage/http_config/dQw4w9WgXcQ/example.com。本插件只支持方式B。