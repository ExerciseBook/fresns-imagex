<?php

namespace Plugins\ImageX\Controllers;

use App\Fresns\Api\Traits\ApiResponseTrait;
use Fresns\CmdWordManager\FresnsCmdWord;
use Fresns\CmdWordManager\CmdWordRespons;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

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

        /**
         * @var CmdWordRespons $data
         */
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

        $t = Cache::get('imagex:uploadsession:' . $data['session'], null);
        if ($t == null) {
            return $this->failure(30000, 'session invalid');
        }

        $uploadResult = $data['uploadResult'];
        $fileInfo = [
            'name' => $uploadResult['FileName'],
            'mime' => '',
            'extension' => '',
            'size' => $uploadResult['ImageSize'], // 单位 Byte
            'md5' => '',
            'sha' => '',
            'shaType' => '',
            'path' => $uploadResult['SourceUri'],
            'imageWidth' => intval($uploadResult['ImageWidth']) ?? 0,
            'imageHeight' => intval($uploadResult['ImageHeight']) ?? 0,
            'videoTime' => 0,
            'videoCoverPath' => null,
            'videoGifPath' => null,
            'audioTime' => 0,
            'transcodingState' => 3,
            'moreJson' => null,
            'originalPath' => null,
            'rating' => null,
            'remark' => null,
        ];

        $bodyInfo = $t;
        $bodyInfo['fileInfo'] = [$fileInfo];

        $fresnsResp = FresnsCmdWord::plugin('Fresns')->uploadFileInfo($bodyInfo);
        if ($fresnsResp->isErrorResponse()) {
            return $fresnsResp->getErrorResponse();
        }

        return $fresnsResp->getOrigin();
    }
}