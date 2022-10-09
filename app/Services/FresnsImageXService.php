<?php


namespace Plugins\ImageX\Services;

use App\Helpers\FileHelper;
use App\Models\File;
use App\Utilities\FileUtility;
use ExerciseBook\Flysystem\ImageX\ImageXAdapter;
use ExerciseBook\Flysystem\ImageX\ImageXConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Flysystem\Config;
use Symfony\Component\Uid\UuidV4;

class FresnsImageXService
{
    protected int $storageId = 21;

    protected string $defaultBucketName = "";

    protected array $userConfig = [];

    private ImageXConfig $imagexConfig;

    private ImageXAdapter $adapter;

    private string $fileRetrievingSignatureToken;

    public function __construct(int $type)
    {
        $settings = FileHelper::fresnsFileStorageConfigByType($type);

        $config = new ImageXConfig();
        $config->region = Arr::get($settings, 'bucketArea', 'cn-north-1');
        if ($config->region == null || strlen($config->region) == 0) {
            $config->region = 'cn-north-1';
        }
        $config->accessKey = Arr::get($settings, 'secretId', '');
        $config->secretKey = Arr::get($settings, 'secretKey', '');
        $config->serviceId = Arr::get($settings, 'bucketName', '');
        $config->domain = Arr::get($settings, 'bucketDomain', '');

//        $this->imagePreviewTemplate = $this->read_template($settings->get('exercisebook-fof-upload-imagex.imagexConfig.imagePreviewTemplate', ''));
//        $this->imageFullscreenTemplate = $this->read_template($settings->get('exercisebook-fof-upload-imagex.imagexConfig.imageFullscreenTemplate', ''));

        $this->fileRetrievingSignatureToken = Arr::get($settings, 'antiLinkKey', '');
        $this->imagexConfig = $config;

        $this->adapter = new ImageXAdapter($this->imagexConfig);
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
        return $this->fileRetrievingSignatureToken != null && strlen($this->fileRetrievingSignatureToken) > 0;
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
     * @param $file File
     * @param $template string
     * @return string
     */
    public function generateUrl($file, $template)
    {
        if ($this->needSignature()) {
            if (Str::startsWith($file->type, 'image/') && $this->isTemplate($template)) {
                return "//" . $this->signPath('/' . $file->path . $template);
            } else {
                return "//" . $this->signPath('/' . $file->path);
            }
        } else {
            if (Str::startsWith($file->type, 'image/') && $this->isTemplate($template)) {
                return "//" . $this->imagexConfig->domain . '/' . $file->path . $template;
            } else {
                return "//" . $this->imagexConfig->domain . '/' . $file->path;
            }
        }
    }


    public function getUploadToken(UploadToken $uploadToken)
    {
        $path = $uploadToken->name;

        // Sign
        $applyParams = [];
        $applyParams["Action"] = "ApplyImageUpload";
        $applyParams["Version"] = "2018-08-01";
        $applyParams["ServiceId"] = $this->imagexConfig->serviceId;
        $applyParams["UploadNum"] = 1;
        $applyParams["StoreKeys"] = [$path];
        $queryStr = http_build_query($applyParams);

        $response = $this->adapter->getClient()->applyUploadImage(['query' => $queryStr]);

        $applyResponse = json_decode($response, true);
        if (isset($applyResponse["ResponseMetadata"]["Error"])) {
            throw new \LogicException(sprintf("uploadImages: request id %s error %s", $applyResponse["ResponseMetadata"]["RequestId"], $applyResponse["ResponseMetadata"]["Error"]["Message"]));
        }

        $uploadAddr = $applyResponse['Result']['UploadAddress'];
        if (count($uploadAddr['UploadHosts']) == 0) {
            throw new \LogicException("uploadImages: no upload host found");
        }
        $uploadHost = $uploadAddr['UploadHosts'][0];
        if (count($uploadAddr['StoreInfos']) != 1) {
            throw new \LogicException("uploadImages: store infos num != upload num");
        }

        return [
            'host' => $uploadHost,
            'storeUri' => $uploadAddr['StoreInfos'][0]["StoreUri"],
            'token' => $uploadAddr['StoreInfos'][0]["Auth"],
            'expireTime' => time(),
        ];
    }

    public function uploadFile(UploadFile $uploadFile)
    {
        $path = FileHelper::fresnsFileStoragePath($uploadFile->type, $uploadFile->usageType) . "/" . (new UuidV4())->toRfc4122();
        $this->getAdapter()->writeStream($path, $uploadFile->file, new Config());

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
            'md5' => $stat['md5'] ?? null,
        ];

        $uploadFileInfo = FileUtility::saveFileInfoToDatabase($bodyInfo, $path, $this->file);

        @unlink($uploadFile->file->path());
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

        // TODO 我到底得到了个啥？
        $uploadFileInfos = FileUtility::uploadFileInfo($bodyInfo);

        $data = [];
        foreach ($uploadFileInfos as $item) {
            $data[] = $item->getFileInfo();
        }

        return $data;
    }

    public function getAntiLinkFileInfo(AntiLinkFileInfo $antiLinkFileInfo)
    {
        if (! $this->needSignature()) {
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
                'imageDefaultUrl', 'imageConfigUrl', 'imageAvatarUrl', 'imageRatioUrl', 'imageSquareUrl', 'imageBigUrl',
                'videoCoverUrl', 'videoGifUrl', 'videoUrl',
                'audioUrl',
                'documentUrl', 'documentPreviewUrl',
            ];

            foreach ($keys as $key) {
                if (!empty($fileInfo[$key])) {
                    $fileInfo[$key] = $this->generateUrl($fileInfo[$key], "");
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
        if (! $this->needSignature()) {
            return null;
        }

        /** @var File $file */
        $file = $this->getFileByFileIdOrFid($antiLinkFileInfoList->fileIdOrFid);

        $originalPath = $file->original_path;
        if (! $originalPath) {
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
            cache()->forget('imagex_file_antilink_'.$file->id);
            cache()->forget('imagex_file_antilink_'.$file->fid);
        }
        return true;
    }
}
