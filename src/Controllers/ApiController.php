<?php

namespace Plugins\ImageX\Controllers;

use App\Fresns\Api\Traits\ApiResponseTrait;
use App\Helpers\CacheHelper;
use App\Helpers\FileHelper;
use Fresns\CmdWordManager\FresnsCmdWord;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Plugins\ImageX\Configuration\Constants;

class ApiController extends Controller
{
    use ApiResponseTrait;

    public function applyUpload(Request $request)
    {
        $data = $request->all();
        $v = Validator::make($data, [
            'type' => ['integer', 'required'],
            'count' => ['integer'],
        ]);

        if ($v->fails()) {
            return $this->failure(30000, $v->messages()->toJson());
        }

        $uploadTokenResp = FresnsCmdWord::plugin('ImageX')->getUploadToken([
            'type' => $data['type'],
            'count' => $data['filesCount'] ?? 1,
        ]);

        if ($uploadTokenResp->isErrorResponse()) {
            return $uploadTokenResp->getErrorResponse();
        }
        return $uploadTokenResp->getOrigin();
    }

    public function commitUpload(Request $request, string $sts)
    {
        $data = $request->all();
        $data['sts'] = $sts;

        $v = Validator::make($data, [
            'sts' => ['string', 'required'],
            'session' => ['string', 'required'],
        ]);
        if ($v->fails()) {
            return $this->failure(30000, $v->messages()->toJson());
        }

        $t = CacheHelper::get('imagex:uploadsession:' . $data['session'], Constants::$cacheTags);
        if ($t == null) {
            return $this->failure(30000, 'session invalid');
        }

        $platformId = Cookie::get('fresns_plugin_cloudinary_platform_id');
        $authUid = Cookie::get('fresns_plugin_cloudinary_auth_uid');

        $t['platformId'] = $platformId;
        $t['usageType'] = $request->attributes->get('usageType');
        $t['tableName'] = $request->attributes->get('tableName');
        $t['tableColumn'] = $request->attributes->get('tableColumn');
        $t['tableId'] = $request->attributes->get('tableId');
        $t['tableKey'] = $request->attributes->get('tableKey');
        $t['uid'] = $authUid;
        $t['uploaded'] = true;

        $uploadResult = $data['uploadResult'];
        $fileInfo = [
            'name' => $uploadResult['FileName'],
            'mime' => '',
            'extension' => pathinfo($uploadResult['FileName'], PATHINFO_EXTENSION),
            'size' => $uploadResult['ImageSize'], // 单位 Byte
            'md5' => '',
            'sha' => '',
            'shaType' => '',
            'path' => $uploadResult['Uri'],
            'imageWidth' => intval($uploadResult['ImageWidth']) ?? 0,
            'imageHeight' => intval($uploadResult['ImageHeight']) ?? 0,
            'videoTime' => 0,
            'videoPosterPath' => null,
            'audioTime' => 0,
            'transcodingState' => 3,
            'moreJson' => null,
            'originalPath' => null,
            'rating' => null,
            'remark' => null,
            'type' => $t['type'],
            'width' => null,
            'height' => null,
        ];

        $commitRpcReq = $t;
        $commitRpcReq['fileInfo'] = $fileInfo;
        $commitRpcResp = FresnsCmdWord::plugin('ImageX')->uploadFileInfo($commitRpcReq);
        if ($commitRpcResp->isErrorResponse()) {
            return $commitRpcResp->getErrorResponse();
        }

        $fileInfo = FileHelper::fresnsFileInfoById($commitRpcResp->getData('fid'), $t);
        return $this->success($fileInfo);
    }
}
