<?php

namespace Plugins\ImageX\Services;

use Fresns\CmdWordManager\Traits\CmdWordResponseTrait;

class CmdWordService
{
    use CmdWordResponseTrait;

    public function getUploadToken(array $wordBody)
    {
        return [];
    }

    public function uploadFile(array $wordBody)
    {
        return [];
    }

    public function uploadFileInfo(array $wordBody)
    {
        return [];
    }

    public function getAntiLinkFileInfo(array $wordBody)
    {
        return [];
    }

    public function getAntiLinkFileInfoList(array $wordBody)
    {
        return [];
    }

    public function getAntiLinkFileOriginalUrl(array $wordBody)
    {
        return [];
    }

    public function logicalDeletionFiles(array $wordBody)
    {
        return [];
    }

    public function physicalDeletionFiles(array $wordBody)
    {
        return [];
    }

    public function audioVideoTranscoding(array $wordBody)
    {
        return [];
    }
}
