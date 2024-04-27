<?php

namespace Plugins\ImageX\Services;

use App\Utilities\ConfigUtility;
use Fresns\CmdWordManager\Traits\CmdWordResponseTrait;
use Illuminate\Support\Facades\Log;

class CmdWordService
{
    use CmdWordResponseTrait;

    public function __construct()
    {
    }

    public function getUploadToken(array $wordBody)
    {
        $uploadToken = new UploadToken($wordBody);
        $imagexService = new FresnsImageXService($uploadToken->type);
        $ret = $imagexService->getUploadToken($uploadToken);
        return $this->success($ret);
    }

    public function uploadFile(array $wordBody)
    {
        $uploadFile = new UploadFile($wordBody);
        $imagexService = new FresnsImageXService($uploadFile->type);
        $ret = $imagexService->uploadFile($uploadFile);
        if (empty($ret)) {
            return $this->failure(32104, ConfigUtility::getCodeMessage(32104));
        }
        return $this->success($ret);
    }

    public function uploadFileInfo(array $wordBody)
    {
        $uploadFileInfo = new UploadFileInfo($wordBody);
        $imagexService = new FresnsImageXService($uploadFileInfo->type);
        $ret = $imagexService->uploadFileInfo($uploadFileInfo);
        return $this->success($ret);
    }

    public function getAntiLinkFileInfo(array $wordBody)
    {
        Log::debug(json_encode($wordBody));

        $antiLinkFileInfo = new AntiLinkFileInfo($wordBody);
        $imagexService = new FresnsImageXService($antiLinkFileInfo->type);
        $ret = $imagexService->getAntiLinkFileInfo($antiLinkFileInfo);
        return $this->success($ret);
    }

    public function getAntiLinkFileInfoList(array $wordBody)
    {
        $antiLinkFileInfoList = new AntiLinkFileInfoList($wordBody);
        $imagexService = new FresnsImageXService($antiLinkFileInfoList->type);
        $ret = $imagexService->getAntiLinkFileInfoList($antiLinkFileInfoList);
        return $this->success($ret);
    }

    public function getAntiLinkFileOriginalUrl(array $wordBody)
    {
        $antiLinkFileInfoList = new AntiLinkFileOriginalUrl($wordBody);
        $imagexService = new FresnsImageXService($antiLinkFileInfoList->type);
        $ret = $imagexService->getAntiLinkFileOriginalUrl($antiLinkFileInfoList);
        return $this->success($ret);
    }

    public function logicalDeletionFiles(array $wordBody)
    {
        $logicalDeletionFiles = new LogicalDeletionFiles($wordBody);
        $imagexService = new FresnsImageXService($logicalDeletionFiles->type);
        $imagexService->logicalDeletionFiles($logicalDeletionFiles);
        return $this->success();
    }

    public function physicalDeletionFiles(array $wordBody)
    {
        $physicalDeletionFiles = new PhysicalDeletionFiles($wordBody);
        $imagexService = new FresnsImageXService($physicalDeletionFiles->type);
        $imagexService->physicalDeletionFiles($physicalDeletionFiles);
        return $this->success();
    }
}
