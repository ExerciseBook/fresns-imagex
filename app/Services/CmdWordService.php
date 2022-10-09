<?php

namespace Plugins\ImageX\Services;

use Fresns\CmdWordManager\Traits\CmdWordResponseTrait;

class CmdWordService
{
    use CmdWordResponseTrait;

    private FresnsImageXService $imagexService;

    public function __construct(FresnsImageXService $imagexService)
    {
        $this->imagexService = $imagexService;
    }

    public function getUploadToken(array $wordBody)
    {
        $uploadToken = new UploadToken($wordBody);
        $ret = $this->imagexService->getUploadToken($uploadToken);
        return $this->success($ret);
    }

    public function uploadFile(array $wordBody)
    {
        $uploadFile = new UploadFile($wordBody);
        $ret = $this->imagexService->uploadFile($uploadFile);
        return $this->success($ret);
    }

    public function uploadFileInfo(array $wordBody)
    {
        $uploadFileInfo = new UploadFileInfo($wordBody);
        $ret = $this->imagexService->uploadFileInfo($uploadFileInfo);
        return $this->success($ret);
    }

    public function getAntiLinkFileInfo(array $wordBody)
    {
        $antiLinkFileInfo = new AntiLinkFileInfo($wordBody);
        $ret = $this->imagexService->getAntiLinkFileInfo($antiLinkFileInfo);
        return $this->success($ret);
    }

    public function getAntiLinkFileInfoList(array $wordBody)
    {
        $antiLinkFileInfoList = new AntiLinkFileInfoList($wordBody);
        $ret = $this->imagexService->getAntiLinkFileInfoList($antiLinkFileInfoList);
        return $this->success($ret);
    }

    public function getAntiLinkFileOriginalUrl(array $wordBody)
    {
        $antiLinkFileInfoList = new AntiLinkFileOriginalUrl($wordBody);
        $ret = $this->imagexService->getAntiLinkFileOriginalUrl($antiLinkFileInfoList);
        return $this->success($ret);
    }

    public function logicalDeletionFiles(array $wordBody)
    {
        $logicalDeletionFiles = new LogicalDeletionFiles($wordBody);
        $this->imagexService->logicalDeletionFiles($logicalDeletionFiles);
        return $this->success();
    }

    public function physicalDeletionFiles(array $wordBody)
    {
        $physicalDeletionFiles = new PhysicalDeletionFiles($wordBody);
        $this->imagexService->physicalDeletionFiles($physicalDeletionFiles);
        return $this->success();
    }

    public function audioVideoTranscoding(array $wordBody)
    {
        return $this->success();
    }
}
