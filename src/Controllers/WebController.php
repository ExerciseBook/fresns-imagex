<?php

namespace Plugins\ImageX\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\PrimaryHelper;
use App\Models\FileUsage;
use App\Utilities\ConfigUtility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Plugins\ImageX\Configuration\Constants;
use Plugins\ImageX\Services\FresnsImageXService;

class WebController extends Controller
{
    public function upload(Request $request)
    {
        $fresnsResp = \FresnsCmdWord::plugin('Fresns')->verifyUrlAuthorization([
            'urlAuthorization' => $request->authorization,
            'userLogin' => true,
        ]);

        $langTag = $fresnsResp->getData('langTag');
        View::share('langTag', $langTag);

        if ($fresnsResp->isErrorResponse()) {
            return view('ImageX::error', [
                'code' => $fresnsResp->getCode(),
                'message' => $fresnsResp->getMessage(),
            ]);
        }

        $uploadInfo = json_decode(base64_decode(urldecode($request->config)), true);

        // 上传文件必传参数 https://docs.fresns.cn/api/common/upload-file.html
        if (!$uploadInfo['usageType'] || !$uploadInfo['tableName'] || !$uploadInfo['type']) {
            return view('ImageX::error', [
                'code' => 30002,
                'message' => ConfigUtility::getCodeMessage(30002, 'Fresns', $langTag),
            ]);
        }
        if (!$uploadInfo['tableId'] && !$uploadInfo['tableKey']) {
            return view('ImageX::error', [
                'code' => 30002,
                'message' => ConfigUtility::getCodeMessage(30001, 'Fresns', $langTag),
            ]);
        }

        // 获取文件配置
        $fileType = match ($uploadInfo['type']) {
            'image' => 1,
            'video' => 2,
            'audio' => 3,
            'document' => 4,
        };
        $usageType = match ($uploadInfo['usageType']) {
            7 => 'post',
            8 => 'comment',
        };

        $authUserId = PrimaryHelper::fresnsUserIdByUidOrUsername($fresnsResp->getData('uid'));

        $editorConfig = ConfigUtility::getEditorConfigByType($authUserId, $usageType, $langTag);
        $toolbar = $editorConfig['toolbar'][$uploadInfo['type']];

        $uploadConfig = [
            'status' => $toolbar['status'],
            'extensions' => $toolbar['extensions'],
            'inputAccept' => $toolbar['inputAccept'],
            'maxSize' => $toolbar['maxSize'],
            'maxTime' => $toolbar['maxTime'] ?? 0,
            'uploadNumber' => $toolbar['uploadNumber'],
        ];

        $fsLang = ConfigHelper::fresnsConfigByItemKey('language_pack_contents', $langTag);

        $checkHeaders = $fresnsResp->getData();

        // 判断上传文件数量
        $fileCount = FileUsage::where('file_type', $fileType)
            ->where('usage_type', $uploadInfo['usageType'])
            ->where('table_name', $uploadInfo['tableName'])
            ->where('table_column', $uploadInfo['tableColumn'])
            ->where('table_id', $uploadInfo['tableId'])
            ->count();

        $fileCountTip = ConfigUtility::getCodeMessage(36115, 'Fresns', $langTag);

        $fileMax = $uploadConfig['uploadNumber'] - $fileCount;

        $imagex = new FresnsImageXService($fileType);
        $imagexClientAppId = $imagex->getClientAppId();
        $imagexServiceId = $imagex->getServiceId();

        $uploadSessionId = Str::uuid()->toString();
        CacheHelper::put([
            'platformId' => $checkHeaders['platformId'],
            'usageType' => $uploadInfo['usageType'],
            'tableName' => $uploadInfo['tableName'],
            'tableColumn' => $uploadInfo['tableColumn'] ?? 'id',
            'tableId' => $uploadInfo['tableId'] ?? null,
            'tableKey' => $uploadInfo['tableKey'] ?? null,
            'aid' => $checkHeaders['aid'],
            'uid' => $checkHeaders['uid'],
            'type' => $fileType,
        ], 'imagex:uploadsession:' . $uploadSessionId, Constants::$cacheTags, 1, now()->addHour(1));

        return \response()
           ->view('ImageX::upload', compact(
            'langTag',
            'fileType',
            'checkHeaders',
            'fsLang',
            'uploadConfig',
            'fileCount',
            'fileCountTip',
            'fileMax',
            'imagexClientAppId',
            'imagexServiceId',
            'uploadSessionId',
        ),200)->header('Cache-Control', 'no-cache');
    }

}
