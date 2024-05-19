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

    public function getTemporaryUrlFileInfo(array $wordBody)
    {
        Log::debug(json_encode($wordBody));

        $temporaryUrlFileInfo = new TemporaryUrlFileInfo($wordBody);
        $imagexService = new FresnsImageXService($temporaryUrlFileInfo->type);
        $ret = $imagexService->getTemporaryUrlFileInfo($temporaryUrlFileInfo);
        return $this->success($ret);
    }

    public function getTemporaryUrlFileInfoList(array $wordBody)
    {
        $temporaryUrlFileInfoList = new TemporaryUrlFileInfoList($wordBody);
        $imagexService = new FresnsImageXService($temporaryUrlFileInfoList->type);
        $ret = $imagexService->getTemporaryUrlFileInfoList($temporaryUrlFileInfoList);
        return $this->success($ret);
    }

    public function getTemporaryUrlOfOriginalFile(array $wordBody)
    {
        $temporaryUrlOfOriginalFile = new TemporaryUrlOfOriginalFile($wordBody);
        $imagexService = new FresnsImageXService($temporaryUrlOfOriginalFile->type);
        $ret = $imagexService->getTemporaryUrlOfOriginalFile($temporaryUrlOfOriginalFile);
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
