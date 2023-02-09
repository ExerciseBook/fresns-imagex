<?php


namespace Plugins\ImageX\Services;

use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Models\File;
use App\Utilities\FileUtility;
use ExerciseBook\Flysystem\ImageX\ImageXAdapter;
use ExerciseBook\Flysystem\ImageX\ImageXConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use Symfony\Component\Uid\UuidV4;

class FresnsImageXService
{
    protected int $storageId = 21;

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
        $config->region = Arr::get($settings, 'bucketArea', 'cn-north-1');
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
            Cache::set("imagex:sts:" . $sts['AccessKeyID'], $sts);
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
        $cacheExpireAt = now()->addSeconds(60 * 25);

        // 缓存
        $data = cache()->remember($cacheKey, $cacheExpireAt, function () use ($antiLinkFileInfo) {
            $file = $this->getFileByFileIdOrFid($antiLinkFileInfo->fileIdOrFid);
            if (is_null($file)) {
                return null;
            }

            $fileInfo = $file->getFileInfo();
            $keys = [
                'imageDefaultUrl' => "",
                'imageConfigUrl' => $this->getTemplateFromFresns("image_thumb_config"),
                'imageAvatarUrl' => $this->getTemplateFromFresns("image_thumb_avatar"),
                'imageRatioUrl' => $this->getTemplateFromFresns("image_thumb_ratio"),
                'imageSquareUrl' => $this->getTemplateFromFresns("image_thumb_square"),
                'imageBigUrl' => $this->getTemplateFromFresns("image_thumb_big"),

                'videoCoverUrl' => $this->getTemplateFromFresns("video_screenshot"),
                'videoGifUrl' => $this->getTemplateFromFresns("video_gift"),
                'videoUrl' => $this->getTemplateFromFresns("video_transcode"),

                'audioUrl' => "",

                'documentUrl' => "",
                'documentPreviewUrl' => "",
            ];

            foreach ($keys as $k => $v) {
                if ((!empty($fileInfo[$k])) || (($fileInfo['type'] == File::TYPE_VIDEO) && ($k == 'videoCoverUrl'))) {
                    $fileInfo[$k] = $this->generateUrl($file["path"], $v);
                }
            }

            return $fileInfo;
        });

        if (is_null($data)) {
            cache()->forget($cacheKey);
        }

        return $data;
    }

    public function getFileByFileIdOrFid($fileIdOrFid)
    {
        return File::where('id', $fileIdOrFid)->orWhere('fid', $fileIdOrFid)->first();
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
        $files = File::whereIn('id', $physicalDeletionFiles->fileIdsOrFids)->orWhereIn('fid', $physicalDeletionFiles->fileIdsOrFids)->get();

        foreach ($files as $file) {
            $this->getAdapter()->delete($file->path);
            $file->delete();

            // 删除 防盗链 缓存
            cache()->forget('imagex_file_antilink_' . $file->id);
            cache()->forget('imagex_file_antilink_' . $file->fid);
        }
        return true;
    }
}
