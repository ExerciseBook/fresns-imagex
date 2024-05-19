# VolcEngine ImageX Integration for Fresns

[中文介绍](readme_zh.md)

## Feature

1. [Basic] File storage. You can save almost anything you like in ImageX
2. [Powerful] Image processing. You can change your image processing to optimize the bandwidth.
3. [Safe] Url signature. Guest can not get your file without a correct signature from your site.

## Installation

1. Install this plugin by typing plugin identity `ImageX` in your Control Panel.
   Typing `php artisan market:require ImageX` in your shell works also.
2. Enable this plugin.
3. Switch to your fresns storage configuration.

## Configuration

| Section           | Configuration                       | Meaning                                                                                                                                                                             | Example                    |
|-------------------|-------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------|
| Server Config     | Service Provider                    | Set to `ImageX Integration`                                                                                                                                                         | `ImageX Integration`       |
|                   | Secret ID                           | Your access key ID get from [VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)                                                                                         | `AKbalabala`               |
|                   | Secret Key                          | Your secret access key get from [VolcEngine IAM](https://console.volcengine.com/iam/keymanage/)                                                                                     | `RaNd0mTeXT`               |
|                   | Bucket Name                         | Your service ID get from [VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/) and AppID get from [VolcEngine BAF](https://console.volcengine.com/baf/my_app/) | `Service0ID,123456`        |
|                   | Bucket Region                       | Your service region get from [VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/) (1)                                                                         | `cn-north-1`               |
|                   | Bucket Endpoint                     |                                                                                                                                                                                     |                            |
|                   | Access Domain                       | Your service domain set in [VolcEngine ImageX](https://console.volcengine.com/imagex/service_manage/)                                                                               | `https://example.com`      |
|                   | Filesystem Disk                     | Set to `remote`                                                                                                                                                                     | `remote`                   |
| Function Config   | Anti Link Key                       | Your url signature secret (2)                                                                                                                                                       | `rANd0mteXt`               |
|                   | Valid minutes for sign              | Keep the value less then the value set in the page `Anti Link Key` mentioned.                                                                                                       |                            |
| Image Function    | Image Handle Position               | Set to path suffix padding                                                                                                                                                          | `path-end`                 |
|                   | Any other blank in this section     | Starts with `~tplv-`, ends with file extension name                                                                                                                                 | `~tplv-Service0ID-t1.avif` |
| Video Function    | Transcode Parameter                 | Starts with `~tplv-`, ends with file extension name                                                                                                                                 | `~tplv-Service0ID-t2.mp4`  |
|                   | Transcode Parameter Handle Position | Set to path suffix padding                                                                                                                                                          | `path-end`                 |
|                   | Poster Parameter                    | Starts with `~tplv-`, ends with file extension name                                                                                                                                 | `~tplv-Service0ID-t3.png`  |
|                   | Poster Parameter Handle Position    | Set to path suffix padding                                                                                                                                                          | `path-end`                 |
| Audio Function    | `-`                                 | `Not supported`. ImageX doesn't provide any audio processing function. But you can storage your audio file in ImageX.                                                               |                            |
| Document Function | `-`                                 | `Supported`. ImageX can storage any file you like.                                                                                                                                  |                            |

(1) The value is properly one of `cn-north-1`, `ap-singapore-1`, `us-east-1`

(2) Configuration page `https://console.volcengine.com/imagex/service_manage/http_config/{SERVICE_ID}/{DOMAIN}`,
e.g. `https://console.volcengine.com/imagex/service_manage/http_config/dQw4w9WgXcQ/example.com`. This plugin only
supports method C.
