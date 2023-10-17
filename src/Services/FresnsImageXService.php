<?php


namespace Plugins\ImageX\Services;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Helpers\StrHelper;
use App\Models\File;
use App\Utilities\FileUtility;
use ExerciseBook\Flysystem\ImageX\ImageXAdapter;
use ExerciseBook\Flysystem\ImageX\ImageXConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use Plugins\ImageX\Configuration\Constants;
use Symfony\Component\Uid\UuidV4;

class FresnsImageXService
{
    protected int $storageId = File::STORAGE_VOLC_ENGINE;

    private ImageXConfig $imagexConfig;

    private ImageXAdapter $adapter;

    private int $clientAppId;

    private string $fileRetrievingSignatureToken;

    private bool $antiLinkConfigEnabled;

    private int $processingType;

    public function __construct(int $type)
    {
        $settings = FileHelper::fresnsFileStorageConfigByType($type);

        $bucketName = explode(',', Arr::get($settings, 'bucketName', ''), 2);
        $serviceId = $bucketName[0];
        $clientAppId = 0;
        if (count($bucketName) > 1) {
            $clientAppId = intval($bucketName[1]);
        }


        $config = new ImageXConfig();
        $config->region = Arr::get($settings, 'bucketRegion', 'cn-north-1');
        if ($config->region == null || strlen($config->region) == 0) {
            $config->region = 'cn-north-1';
        }
        $config->accessKey = Arr::get($settings, 'secretId', '');
        $config->secretKey = Arr::get($settings, 'secretKey', '');
        $config->serviceId = $serviceId;
        $this->clientAppId = $clientAppId;
        $config->domain = Arr::get($settings, 'bucketDomain', '');

        $this->fileRetrievingSignatureToken = Arr::get($settings, 'antiLinkKey', '') ?? '';
        $this->imagexConfig = $config;

        $this->adapter = new ImageXAdapter($this->imagexConfig);

        $this->processingType = $type;
        $this->antiLinkConfigEnabled = Arr::get($settings, 'antiLinkConfigStatus', 'false');
    }

    public function isImageProcessing()
    {
        return $this->processingType == 1;
    }

    public function isVideoProcessing()
    {
        return $this->processingType == 2;
    }

    public function isAudioProcessing()
    {
        return $this->processingType == 3;
    }

    public function isGeneralProcessing()
    {
        return $this->processingType == 1;
    }

    /**
     * @return ImageXAdapter
     */
    public function getAdapter(): ImageXAdapter
    {
        return $this->adapter;
    }

    /**
     * @return bool
     */
    public function needSignature()
    {
        return $this->antiLinkConfigEnabled && $this->fileRetrievingSignatureToken != null && strlen($this->fileRetrievingSignatureToken) > 0;
    }

    /**
     * @param $template string
     * @return bool
     */
    public function isTemplate($template)
    {
        return $template != null && strlen($template) > 0;
    }

    /**
     * @param $signPath string
     * @return string
     */
    public function signPath($signPath)
    {
        $sign_ts = time();
        $sign_payload = sprintf("%s%s%x", $this->fileRetrievingSignatureToken, $signPath, $sign_ts);
        $sign = strtolower(md5($sign_payload));
        return sprintf("%s/%s/%x%s", $this->imagexConfig->domain, $sign, $sign_ts, $signPath);
    }

    /**
     * @param $filePath string
     * @param $template string
     * @return string
     */
    public function generateUrl($filePath, $template)
    {
        if ($this->needSignature()) {
            if (($this->isImageProcessing() || $this->isVideoProcessing()) && $this->isTemplate($template)) {
                return $this->signPath('/' . $filePath . $template);
            } else {
                return $this->signPath('/' . $filePath);
            }
        } else {
            if (($this->isImageProcessing() || $this->isVideoProcessing()) && $this->isTemplate($template)) {
                return $this->imagexConfig->domain . '/' . $filePath . $template;
            } else {
                return $this->imagexConfig->domain . '/' . $filePath;
            }
        }
    }

    /**
     * @param $key
     * @return string
     */
    private function getTemplateFromFresns($key)
    {
        return $this->readTemplate(ConfigHelper::fresnsConfigByItemKey($key) ?? "");
    }

    /**
     * @param $ret
     * @return string
     */
    private function readTemplate($ret)
    {
        if (!is_string($ret)) {
            $ret = '';
        }

        if (strlen($ret) == 0) {
            return '';
        }

        if (!str_starts_with($ret, '~')) {
            $ret = '~' . $ret;
        }

        if (!str_contains($ret, '.')) {
            $ret .= '.image';
        }

        return $ret;
    }

    /**
     * @return int
     */
    public function getClientAppId()
    {
        return $this->clientAppId;
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->imagexConfig->serviceId;
    }

    public function getUploadToken(UploadToken $uploadToken)
    {
        $uploadNum = $uploadToken->count ?? 1;
        $expireTime = 3600;

        $ret = [];
        for ($i = 0; $i < $uploadNum; $i++) {
            $storeKey = Str::uuid()->toString();
            $sts = $this->adapter->getClient()->getUploadAuth([$this->getServiceId()], $expireTime, $storeKey);
            $sts['storeKey'] = $storeKey;
            CacheHelper::put($sts, "imagex:sts:" . $sts['AccessKeyID'], Constants::$cacheTags, 1, now()->addHour(1));
            $ret[] = $sts;
        }

        return [
            'expireTime' => $expireTime,
            'uploadInfo' => $ret,
        ];
    }

    public function uploadFile(UploadFile $uploadFile)
    {
        $path = FileHelper::fresnsFileStoragePath($uploadFile->type, $uploadFile->usageType) . "/" . (new UuidV4())->toRfc4122();

        /**
         * @type \Illuminate\Http\UploadedFile $file
         */
        $file = $uploadFile->file;
        $resource = fopen($file->path(), "r");
        $this->getAdapter()->writeStream($path, $resource, new Config());

        $bodyInfo = [
            'platformId' => $uploadFile->platformId,
            'usageType' => $uploadFile->usageType,
            'tableName' => $uploadFile->tableName,
            'tableColumn' => $uploadFile->tableColumn,
            'tableId' => $uploadFile->tableId,
            'tableKey' => $uploadFile->tableKey,
            'aid' => $uploadFile->aid ?: null,
            'uid' => $uploadFile->uid ?: null,
            'type' => $uploadFile->type,
            'moreJson' => $uploadFile->moreJson ?: null,
            'md5' => null,
        ];

        $uploadFileInfo = FileUtility::saveFileInfoToDatabase($bodyInfo, $path, $file);

        @unlink($file->path());
        return $uploadFileInfo;
    }

    public function uploadFileInfo(UploadFileInfo $uploadFileInfo)
    {
        $bodyInfo = [
            'platformId' => $uploadFileInfo->platformId,
            'usageType' => $uploadFileInfo->usageType,
            'tableName' => $uploadFileInfo->tableName,
            'tableColumn' => $uploadFileInfo->tableColumn,
            'tableId' => $uploadFileInfo->tableId,
            'tableKey' => $uploadFileInfo->tableKey,
            'aid' => $uploadFileInfo->aid ?: null,
            'uid' => $uploadFileInfo->uid ?: null,
            'type' => $uploadFileInfo->type,
            'fileInfo' => $uploadFileInfo->fileInfo,
        ];

        $uploadFileInfos = FileUtility::uploadFileInfo($bodyInfo);

        $data = [];
        foreach ($uploadFileInfos as $item) {
            $data[] = $item;
        }

        return $data;
    }

    public function getAntiLinkFileInfo(AntiLinkFileInfo $antiLinkFileInfo)
    {
        if (!$this->needSignature()) {
            return null;
        }

        $cacheKey = 'imagex_file_antilink_' . $antiLinkFileInfo->fileIdOrFid;

        // 缓存
        $data = CacheHelper::get($cacheKey, Constants::$cacheTags);
        if (empty($data)) {
            $file = $this->getFileByFileIdOrFid($antiLinkFileInfo->fileIdOrFid);
            if (is_null($file)) {
                return null;
            }

            $fileInfo = $file->getFileInfo();
            $keys = [
                'imageConfigUrl' => $this->getTemplateFromFresns("image_thumb_config"),
                'imageRatioUrl' => $this->getTemplateFromFresns("image_thumb_ratio"),
                'imageSquareUrl' => $this->getTemplateFromFresns("image_thumb_square"),
                'imageBigUrl' => $this->getTemplateFromFresns("image_thumb_big"),

                'videoPosterUrl' => $this->getTemplateFromFresns("video_poster_parameter"),
                'videoUrl' => $this->getTemplateFromFresns("video_transcode_parameter"),

                'audioUrl' => $this->getTemplateFromFresns("audio_transcode_parameter"),

                'documentPreviewUrl' => $this->getTemplateFromFresns("document_online_preview"),
            ];
            foreach ($keys as $k => $v) {
                if ($k == 'documentPreviewUrl') {
                    $documentUrl = $this->generateUrl($file["path"], $v);
                    $fileInfo['documentPreviewUrl'] = FileHelper::fresnsFileDocumentPreviewUrl($documentUrl, $fileInfo['fid'], $fileInfo['extension']);
                    continue;
                }

                if ((!empty($fileInfo[$k])) ||
                    (($fileInfo['type'] == File::TYPE_VIDEO) && ($k == 'videoPosterUrl' || $k == 'videoUrl')) ||
                    (($fileInfo['type'] == File::TYPE_IMAGE) && ($k == 'image_thumb_config' || $k == 'image_thumb_ratio' || $k == 'image_thumb_square' || $k == 'image_thumb_big'))
                ) {
                    $fileInfo[$k] = $this->generateUrl($file["path"], $v);
                }
            }

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType($file->type, null, 2);
            CacheHelper::put($fileInfo, $cacheKey, Constants::$cacheTags, 1, $cacheTime);

            $data = $fileInfo;
        };

        if (is_null($data)) {
            CacheHelper::forgetFresnsKey($cacheKey, Constants::$cacheTags);
        }

        return $data;
    }

    public function getFileByFileIdOrFid($fileIdOrFid)
    {
        if (StrHelper::isPureInt($fileIdOrFid)) {
            return File::where('id', $fileIdOrFid)->first();
        } else {
            return File::where('fid', $fileIdOrFid)->first();
        }
    }

    public function getAntiLinkFileInfoList(AntiLinkFileInfoList $antiLinkFileInfoList)
    {
        $data = [];
        foreach ($antiLinkFileInfoList->fileIdsOrFids as $fileIdOrFid) {
            $antiLinkFileInfo = new AntiLinkFileInfo([
                'type' => $antiLinkFileInfoList->type,
                'fileIdOrFid' => $fileIdOrFid,
            ]);

            $data[] = $this->getAntiLinkFileInfo($antiLinkFileInfo);
        }
        return $data;
    }

    public function getAntiLinkFileOriginalUrl(AntiLinkFileOriginalUrl $antiLinkFileInfoList)
    {
        if (!$this->needSignature()) {
            return null;
        }

        /** @var File $file */
        $file = $this->getFileByFileIdOrFid($antiLinkFileInfoList->fileIdOrFid);

        $originalPath = $file->original_path;
        if (!$originalPath) {
            $originalPath = $file->path;
        }

        return [
            'originalUrl' => $this->generateUrl($originalPath, "")
        ];
    }

    public function logicalDeletionFiles(LogicalDeletionFiles $logicalDeletionFiles)
    {
        FileUtility::logicalDeletionFiles($logicalDeletionFiles->fileIdsOrFids);
        return true;
    }

    public function physicalDeletionFiles(PhysicalDeletionFiles $physicalDeletionFiles)
    {
        foreach ($physicalDeletionFiles->fileIdsOrFids as $id) {
            if (StrHelper::isPureInt($id)) {
                $file = File::where('id', $id)->first();
            } else {
                $file = File::where('fid', $id)->first();
            }

            // 兼容旧版本的错误逻辑
            $uriPrefix = $this->getAdapter()->getUriPrefix() . '/';
            $path = $file->path;
            if (str_starts_with($path, $uriPrefix)) {
                $path = substr($path, strlen($uriPrefix));
            }
            $this->getAdapter()->delete($path);
            $file->update([
                'physical_deletion' => 1,
            ]);
            $file->delete();

            // 删除 防盗链 缓存
            CacheHelper::forgetFresnsFileUsage($file->fid);
            CacheHelper::forgetFresnsKey('imagex_file_antilink_' . $file->id, Constants::$cacheTags);
            CacheHelper::forgetFresnsKey('imagex_file_antilink_' . $file->fid, Constants::$cacheTags);
        }
        return true;
    }
}
